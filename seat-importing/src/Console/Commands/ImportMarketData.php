<?php

namespace Apokavkos\SeatImporting\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Apokavkos\SeatImporting\Models\MarketHub;
use Apokavkos\SeatImporting\Models\MarketImportLog;
use Apokavkos\SeatImporting\Services\MarketMetricsService;

class ImportMarketData extends Command
{
    protected $signature = 'seat:importing:import
        {--hub=          : Hub ID to import for (defaults to all active hubs)}
        {--source=fuzzwork_csv : Import source (fuzzwork_csv|tycoon_csv)}
        {--file=         : Path to a local CSV file to import}
        {--download      : Automatically download the latest data from Fuzzwork}
        {--jita-region=10000002 : Region ID to download from Fuzzwork}
        {--dry-run       : Parse and validate without writing to DB}';

    protected $description = 'Import market data from Fuzzwork or Tycoon CSV dumps into the seat-importing plugin database.';

    private MarketMetricsService $metricsService;

    public function __construct(MarketMetricsService $metricsService)
    {
        parent::__construct();
        $this->metricsService = $metricsService;
    }

    public function handle(): int
    {
        $hubId      = $this->option('hub')   ? (int) $this->option('hub')   : null;
        $source     = $this->option('source') ?? config('seat-importing.import.default_source', 'fuzzwork_csv');
        $filePath   = $this->option('file')  ?? null;
        $download   = (bool) $this->option('download');
        $regionId   = $this->option('jita-region');
        $dryRun     = (bool) $this->option('dry-run');

        if (! in_array($source, ['fuzzwork_csv', 'tycoon_csv'], true)) {
            $this->error("Unknown source '{$source}'. Supported: fuzzwork_csv, tycoon_csv.");
            return self::FAILURE;
        }

        // Determine which hubs to process
        if ($hubId !== null) {
            $hubs = MarketHub::where('id', $hubId)->where('is_active', true)->get();
            if ($hubs->isEmpty()) {
                $this->error("Hub #{$hubId} not found or is inactive.");
                return self::FAILURE;
            }
        } else {
            $hubs = MarketHub::where('is_active', true)->orderBy('id')->get();
            if ($hubs->isEmpty()) {
                $this->warn('No active hubs found. Create a hub first.');
                return self::SUCCESS;
            }
        }

        // Resolve or Download the CSV path
        $resolvedFile = $filePath;
        
        if ($download && $source === 'fuzzwork_csv') {
            $this->info("Downloading latest data for region {$regionId} from Fuzzwork...");
            $url = "https://market.fuzzwork.co.uk/aggregates/region/{$regionId}.csv";
            $downloadPath = config('seat-importing.import.import_path') . "/region_{$regionId}.csv";
            
            if (!File::exists(dirname($downloadPath))) {
                File::makeDirectory(dirname($downloadPath), 0755, true);
            }

            try {
                $response = Http::get($url);
                if ($response->successful()) {
                    File::put($downloadPath, $response->body());
                    $resolvedFile = $downloadPath;
                    $this->info("Download complete: {$resolvedFile}");
                } else {
                    $this->error("Failed to download from Fuzzwork: " . $response->status());
                    return self::FAILURE;
                }
            } catch (\Exception $e) {
                $this->error("Download error: " . $e->getMessage());
                return self::FAILURE;
            }
        }

        if (!$resolvedFile) {
            $resolvedFile = config('seat-importing.import.import_path') . '/market.csv';
        }

        if (! file_exists($resolvedFile)) {
            $this->error("CSV file not found: {$resolvedFile}");
            $this->line("Tip: Use --download to fetch Jita data automatically.");
            return self::FAILURE;
        }

        $this->line("<info>Source:</info> {$source}");
        $this->line("<info>File:</info>   {$resolvedFile}");
        $this->line("<info>Hubs:</info>   " . $hubs->pluck('name')->implode(', '));

        if ($dryRun) {
            $this->warn('DRY RUN — no data will be written.');
        }

        $this->line('Parsing CSV…');

        try {
            $rows = $source === 'fuzzwork_csv'
                ? $this->parseFuzzworkCsv($resolvedFile)
                : $this->parseTycoonCsv($resolvedFile);
        } catch (\Exception $e) {
            $this->error('CSV parse error: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->line('Parsed ' . count($rows) . ' rows.');

        if ($dryRun) {
            $this->info('Dry run complete — ' . count($rows) . ' rows validated, nothing written.');
            return self::SUCCESS;
        }

        $sdeTypes = $this->loadSdeTypes(array_column($rows, 'type_id'));

        $exitCode = self::SUCCESS;

        foreach ($hubs as $hub) {
            $this->line('');
            $this->line("Processing hub: <comment>{$hub->name}</comment>");

            $log = MarketImportLog::create([
                'hub_id'         => $hub->id,
                'source'         => $source,
                'filename'       => $resolvedFile,
                'status'         => 'running',
                'rows_processed' => 0,
                'rows_failed'    => 0,
                'started_at'     => Carbon::now(),
            ]);

            try {
                [$processed, $failed] = $this->importRowsForHub($hub, $rows, $sdeTypes);

                $log->update([
                    'status'         => 'complete',
                    'rows_processed' => $processed,
                    'rows_failed'    => $failed,
                    'completed_at'   => Carbon::now(),
                ]);

                $this->metricsService->flushHubCache($hub->id);
                $this->metricsService->warmHubCache($hub->id);
                $this->info("  ✓ {$processed} rows imported, {$failed} failed.");
            } catch (\Exception $e) {
                $log->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at'  => Carbon::now(),
                ]);

                $this->error("  ✗ Import failed for hub {$hub->name}: " . $e->getMessage());
                $exitCode = self::FAILURE;
            }
        }

        $this->line('');
        $this->info('Import complete.');
        return $exitCode;
    }

    private function parseFuzzworkCsv(string $path): array
    {
        $rows   = [];
        $handle = fopen($path, 'r');
        if ($handle === false) throw new \RuntimeException("Cannot open file: {$path}");

        $headers = null;
        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map('strtolower', array_map('trim', $line));
                continue;
            }
            $row = array_combine($headers, $line);
            $typeId      = (int) ($row['typeid'] ?? $row['type_id'] ?? 0);
            $jitaSell    = (float) ($row['sell_min'] ?? $row['sell_percentile'] ?? 0);
            $jitaBuy     = (float) ($row['buy_max'] ?? 0);
            $sellVolume  = (float) ($row['sell_volume'] ?? 0);

            if ($typeId <= 0) continue;

            $rows[] = [
                'type_id'       => $typeId,
                'jita_sell'     => $jitaSell,
                'jita_buy'      => $jitaBuy,
                'local_sell'    => $jitaSell,
                'local_buy'     => $jitaBuy,
                'weekly_volume' => $sellVolume > 0 ? $sellVolume / 4 : 0,
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
        if ($handle === false) throw new \RuntimeException("Cannot open file: {$path}");

        $headers = null;
        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map('strtolower', array_map('trim', $line));
                continue;
            }
            $row = array_combine($headers, $line);
            $typeId     = (int) ($row['typeid'] ?? 0);
            $sellMin    = (float) ($row['sell_min'] ?? 0);
            $buyMax     = (float) ($row['buy_max'] ?? 0);
            $sellVol    = (float) ($row['sell_volume'] ?? 0);

            if ($typeId <= 0) continue;

            $rows[] = [
                'type_id'       => $typeId,
                'jita_sell'     => $sellMin,
                'jita_buy'      => $buyMax,
                'local_sell'    => $sellMin,
                'local_buy'     => $buyMax,
                'weekly_volume' => $sellVol,
                'current_stock' => 0,
            ];
        }
        fclose($handle);
        return $rows;
    }

    private function importRowsForHub(MarketHub $hub, array $rows, array $sdeTypes): array
    {
        $iskPerM3   = $hub->effectiveIskPerM3();
        $batchSize  = config('seat-importing.import.batch_size', 500);
        $dataDate   = Carbon::today()->toDateString();
        $processed  = 0;
        $failed     = 0;

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach (array_chunk($rows, $batchSize) as $chunk) {
            $upsertRows = [];
            foreach ($chunk as $row) {
                try {
                    $typeId   = (int) $row['type_id'];
                    $sdeEntry = $sdeTypes[$typeId] ?? null;
                    $volumeM3 = (float) ($sdeEntry['volume_m3'] ?? 0);
                    $typeName = $sdeEntry['type_name'] ?? $row['type_name'] ?? null;

                    $localSell    = (float) ($row['local_sell'] ?? 0);
                    $localBuy     = (float) ($row['local_buy'] ?? 0);
                    $jitaSell     = (float) ($row['jita_sell'] ?? 0);
                    $weeklyVolume = (float) ($row['weekly_volume'] ?? 0);

                    $metrics = $this->metricsService->calculateMetrics([
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
                        'jita_buy_price'   => (float) ($row['jita_buy'] ?? 0),
                        'current_stock'    => (int) ($row['current_stock'] ?? 0),
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
                } catch (\Exception $e) {
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
            $bar->advance(count($chunk));
        }
        $bar->finish();
        return [$processed, $failed];
    }

    private function loadSdeTypes(array $typeIds): array
    {
        $typeIds = array_unique(array_filter($typeIds));
        if (empty($typeIds)) return [];
        try {
            $sde = DB::connection('sde');
            $rows = $sde->table('invTypes')->select('typeID', 'typeName', 'volume')->whereIn('typeID', $typeIds)->get();
            $result = [];
            foreach ($rows as $row) {
                $result[(int) $row->typeID] = ['type_name' => $row->typeName, 'volume_m3' => (float) $row->volume];
            }
            return $result;
        } catch (\Exception) {
            return [];
        }
    }
}
