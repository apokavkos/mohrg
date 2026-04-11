<?php

namespace Apokavkos\SeatImporting\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Apokavkos\SeatImporting\Models\MarketHub;
use Apokavkos\SeatImporting\Models\MarketImportLog;
use Apokavkos\SeatImporting\Services\MarketMetricsService;

/**
 * Queued job that mirrors the ImportMarketData console command logic.
 * Dispatched from the web UI or programmatically for async large imports.
 */
class ProcessMarketImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Maximum seconds before the job times out */
    public int $timeout = 3600;

    /** @var int Number of times the job may be attempted */
    public int $tries = 2;

    /**
     * @param  int|null    $hubId    Specific hub ID, or null to process all active hubs
     * @param  string      $source   fuzzwork_csv|tycoon_csv
     * @param  string|null $filePath Local CSV file path; null uses the config default
     */
    public function __construct(
        public readonly ?int    $hubId,
        public readonly string  $source   = 'fuzzwork_csv',
        public readonly ?string $filePath = null,
    ) {}

    public function handle(MarketMetricsService $metricsService): void
    {
        $resolvedFile = $this->filePath
            ?? config('seat-importing.import.import_path') . '/market.csv';

        if (! file_exists($resolvedFile)) {
            throw new \RuntimeException("CSV file not found: {$resolvedFile}");
        }

        // Determine which hubs to process
        $hubs = $this->hubId !== null
            ? MarketHub::where('id', $this->hubId)->where('is_active', true)->get()
            : MarketHub::where('is_active', true)->orderBy('id')->get();

        if ($hubs->isEmpty()) {
            return;
        }

        // Parse CSV once, reuse across hubs
        $rows    = $this->source === 'fuzzwork_csv'
            ? $this->parseFuzzworkCsv($resolvedFile)
            : $this->parseTycoonCsv($resolvedFile);

        $sdeTypes = $this->loadSdeTypes(array_column($rows, 'type_id'));

        foreach ($hubs as $hub) {
            $log = MarketImportLog::create([
                'hub_id'         => $hub->id,
                'source'         => $this->source,
                'filename'       => $resolvedFile,
                'status'         => 'running',
                'rows_processed' => 0,
                'rows_failed'    => 0,
                'started_at'     => Carbon::now(),
            ]);

            try {
                [$processed, $failed] = $this->importRowsForHub($hub, $rows, $sdeTypes, $metricsService);

                $log->update([
                    'status'         => 'complete',
                    'rows_processed' => $processed,
                    'rows_failed'    => $failed,
                    'completed_at'   => Carbon::now(),
                ]);

                $metricsService->flushHubCache($hub->id);
            } catch (\Exception $e) {
                $log->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at'  => Carbon::now(),
                ]);

                throw $e; // Re-throw so the queue marks the job as failed
            }
        }
    }

    // -------------------------------------------------------------------------
    // CSV parsers (duplicated from ImportMarketData command for job independence)
    // -------------------------------------------------------------------------

    private function parseFuzzworkCsv(string $path): array
    {
        $rows   = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open file: {$path}");
        }

        $headers = null;

        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map('strtolower', array_map('trim', $line));
                continue;
            }

            $row    = array_combine($headers, $line);
            $typeId = (int) ($row['typeid'] ?? $row['type_id'] ?? 0);

            if ($typeId <= 0) {
                continue;
            }

            $rows[] = [
                'type_id'       => $typeId,
                'jita_sell'     => (float) ($row['sell_min'] ?? $row['sell_percentile'] ?? 0),
                'jita_buy'      => (float) ($row['buy_max'] ?? 0),
                'local_sell'    => (float) ($row['sell_min'] ?? 0),
                'local_buy'     => (float) ($row['buy_max'] ?? 0),
                'weekly_volume' => (float) ($row['sell_volume'] ?? 0) / 4,
                'current_stock' => (int) ($row['sell_orders'] ?? 0),
            ];
        }

        fclose($handle);
        return $rows;
    }

    private function parseTycoonCsv(string $path): array
    {
        $rows   = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open file: {$path}");
        }

        $headers = null;

        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map('strtolower', array_map('trim', $line));
                continue;
            }

            $row    = array_combine($headers, $line);
            $typeId = (int) ($row['typeid'] ?? 0);

            if ($typeId <= 0) {
                continue;
            }

            $rows[] = [
                'type_id'       => $typeId,
                'jita_sell'     => (float) ($row['sell_min'] ?? 0),
                'jita_buy'      => (float) ($row['buy_max'] ?? 0),
                'local_sell'    => (float) ($row['sell_min'] ?? 0),
                'local_buy'     => (float) ($row['buy_max'] ?? 0),
                'weekly_volume' => (float) ($row['sell_volume'] ?? 0),
                'current_stock' => 0,
            ];
        }

        fclose($handle);
        return $rows;
    }

    // -------------------------------------------------------------------------
    // Import logic
    // -------------------------------------------------------------------------

    private function importRowsForHub(
        MarketHub $hub,
        array $rows,
        array $sdeTypes,
        MarketMetricsService $metricsService
    ): array {
        $iskPerM3  = $hub->effectiveIskPerM3();
        $batchSize = config('seat-importing.import.batch_size', 500);
        $dataDate  = Carbon::today()->toDateString();
        $processed = 0;
        $failed    = 0;

        foreach (array_chunk($rows, $batchSize) as $chunk) {
            $upsertRows = [];

            foreach ($chunk as $row) {
                try {
                    $typeId   = (int) $row['type_id'];
                    $sdeEntry = $sdeTypes[$typeId] ?? null;

                    $volumeM3     = (float) ($sdeEntry['volume_m3'] ?? 0);
                    $typeName     = $sdeEntry['type_name'] ?? null;
                    $localSell    = (float) ($row['local_sell'] ?? 0);
                    $localBuy     = (float) ($row['local_buy'] ?? 0);
                    $jitaSell     = (float) ($row['jita_sell'] ?? 0);
                    $jitaBuy      = (float) ($row['jita_buy'] ?? 0);
                    $weeklyVolume = (float) ($row['weekly_volume'] ?? 0);
                    $stock        = (int) ($row['current_stock'] ?? 0);

                    $metrics = $metricsService->calculateMetrics([
                        'local_sell'    => $localSell,
                        'jita_sell'     => $jitaSell,
                        'volume_m3'     => $volumeM3,
                        'weekly_volume' => $weeklyVolume,
                    ], $iskPerM3);

                    $upsertRows[] = [
                        'hub_id'           => $hub->id,
                        'type_id'          => $typeId,
                        'type_name'        => $typeName,
                        'local_sell_price' => $localSell,
                        'local_buy_price'  => $localBuy,
                        'jita_sell_price'  => $jitaSell,
                        'jita_buy_price'   => $jitaBuy,
                        'current_stock'    => $stock,
                        'weekly_volume'    => $weeklyVolume,
                        'volume_m3'        => $volumeM3,
                        'import_cost'      => $metrics['import_cost'],
                        'markup_pct'       => $metrics['markup_pct'],
                        'weekly_profit'    => $metrics['weekly_profit'],
                        'data_date'        => $dataDate,
                        'created_at'       => Carbon::now(),
                        'updated_at'       => Carbon::now(),
                    ];

                    $processed++;
                } catch (\Exception) {
                    $failed++;
                }
            }

            if (! empty($upsertRows)) {
                DB::table('market_item_data')->upsert(
                    $upsertRows,
                    ['hub_id', 'type_id', 'data_date'],
                    [
                        'type_name', 'local_sell_price', 'local_buy_price',
                        'jita_sell_price', 'jita_buy_price', 'current_stock',
                        'weekly_volume', 'volume_m3', 'import_cost',
                        'markup_pct', 'weekly_profit', 'updated_at',
                    ]
                );
            }
        }

        return [$processed, $failed];
    }

    /**
     * Load type names and volumes from SDE. Gracefully returns empty on failure.
     *
     * @param  int[] $typeIds
     * @return array<int, array{type_name: string, volume_m3: float}>
     */
    private function loadSdeTypes(array $typeIds): array
    {
        $typeIds = array_unique(array_filter($typeIds));

        if (empty($typeIds)) {
            return [];
        }

        try {
            $rows = DB::connection('sde')
                ->table('invTypes')
                ->select('typeID', 'typeName', 'volume')
                ->whereIn('typeID', $typeIds)
                ->get();

            $result = [];
            foreach ($rows as $row) {
                $result[(int) $row->typeID] = [
                    'type_name' => $row->typeName,
                    'volume_m3' => (float) $row->volume,
                ];
            }
            return $result;
        } catch (\Exception) {
            return [];
        }
    }
}
