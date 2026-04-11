<?php

namespace Apokavkos\SeatImporting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log for every CSV import run.
 * Tracks source, file, status and row-level counts so operators can diagnose failures.
 *
 * @property int         $id
 * @property int|null    $hub_id
 * @property string      $source           fuzzwork_csv|tycoon_csv
 * @property string|null $filename
 * @property string      $status           pending|running|complete|failed
 * @property int         $rows_processed
 * @property int         $rows_failed
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 */
class MarketImportLog extends Model
{
    protected $table = 'market_import_logs';

    protected $fillable = [
        'hub_id',
        'source',
        'filename',
        'status',
        'rows_processed',
        'rows_failed',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'hub_id'         => 'integer',
        'rows_processed' => 'integer',
        'rows_failed'    => 'integer',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function hub(): BelongsTo
    {
        return $this->belongsTo(MarketHub::class, 'hub_id');
    }

    // -------------------------------------------------------------------------
    // Status Helpers
    // -------------------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Elapsed time in seconds, or null if not yet started/completed.
     */
    public function elapsedSeconds(): ?float
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }
}
