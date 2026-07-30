<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'mapping' => 'array',
            'validation' => 'array',
            'summary' => 'array',
            'errors' => 'array',
            'backup_created_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isImporting(): bool
    {
        return $this->status === 'importing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }

    public function scopeCompleted($q)
    {
        return $q->where('status', 'completed');
    }

    public function scopeForTenant($q, int $tenantId)
    {
        return $q->where('tenant_id', $tenantId);
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '-';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
    }

    public function getTotalRecordsImportedAttribute(): int
    {
        $summary = $this->summary ?? [];
        return ($summary['imported'] ?? 0) + ($summary['updated'] ?? 0);
    }

    public function markAnalyzing(): void
    {
        $this->update(['status' => 'analyzing']);
    }

    public function markReady(): void
    {
        $this->update(['status' => 'ready']);
    }

    public function markImporting(): void
    {
        $this->update([
            'status' => 'importing',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(array $summary): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => 'completed',
            'summary' => $summary,
            'completed_at' => now(),
            'duration_seconds' => $duration,
        ]);
    }

    public function markFailed(array $errors): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => 'failed',
            'errors' => $errors,
            'completed_at' => now(),
            'duration_seconds' => $duration,
        ]);
    }

    public function updateProgress(string $section, int $processed, int $total): void
    {
        $this->update([
            'current_section' => $section,
            'processed_records' => $processed,
            'total_records' => $total,
        ]);
    }
}
