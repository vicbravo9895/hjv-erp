<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SamsaraSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_type',
        'status',
        'started_at',
        'completed_at',
        'synced_records',
        'duration_seconds',
        'error_message',
        'params',
        'additional_data',
        'error_details',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'synced_records' => 'integer',
        'duration_seconds' => 'integer',
        'params' => 'array',
        'additional_data' => 'array',
        'error_details' => 'array',
    ];

    /**
     * Scope for successful syncs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed syncs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for running syncs
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope for specific sync type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('sync_type', $type);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }

    /**
     * Check if sync is considered stuck
     */
    public function isStuck(int $timeoutMinutes = 30): bool
    {
        return $this->status === 'running' && 
               $this->started_at->diffInMinutes(now()) > $timeoutMinutes;
    }

    /**
     * Get success rate for this sync type
     */
    public static function getSuccessRate(string $syncType, int $days = 7): float
    {
        $total = static::ofType($syncType)
            ->where('started_at', '>=', now()->subDays($days))
            ->count();

        if ($total === 0) {
            return 0;
        }

        $successful = static::ofType($syncType)
            ->successful()
            ->where('started_at', '>=', now()->subDays($days))
            ->count();

        return round(($successful / $total) * 100, 2);
    }
}