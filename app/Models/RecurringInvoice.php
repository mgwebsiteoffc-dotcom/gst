<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class RecurringInvoice extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const FREQUENCIES = [
        'daily'       => 'Daily',
        'weekly'      => 'Weekly',
        'monthly'     => 'Monthly',
        'quarterly'   => 'Quarterly',
        'half_yearly' => 'Half-yearly',
        'yearly'      => 'Yearly',
    ];

    public const STATUSES = ['active', 'paused', 'completed', 'cancelled'];

    /** The only document types a schedule may generate. */
    public const DOC_TYPES = [
        'invoice' => 'Invoice',
        'bill_of_supply' => 'Bill of Supply',
    ];

    protected $guarded = [];

    protected $attributes = [
        'doc_type' => 'invoice',
        'tax_mode' => 'gst',
        'frequency' => 'monthly',
        'interval_count' => 1,
        'status' => 'active',
        'auto_finalize' => true,
        'generated_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'next_run_on' => 'date',
            'ends_on' => 'date',
            'auto_finalize' => 'boolean',
            'template' => 'array',
            'last_generated_at' => 'datetime',
        ];
    }

    public function party() { return $this->belongsTo(Party::class); }
    public function documents() { return $this->hasMany(Document::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeActive($q) { return $q->where('status', 'active'); }

    /** Schedules that should have produced a document on or before $date. */
    public function scopeDue($q, ?Carbon $date = null)
    {
        return $q->whereDate('next_run_on', '<=', ($date ?? now())->toDateString());
    }

    /** Human readable schedule description, e.g. "Every 2 months". */
    public function scheduleLabel(): string
    {
        $n = max(1, (int) $this->interval_count);
        $unit = [
            'daily' => ['day', 'days'],
            'weekly' => ['week', 'weeks'],
            'monthly' => ['month', 'months'],
            'quarterly' => ['quarter', 'quarters'],
            'half_yearly' => ['half-year', 'half-years'],
            'yearly' => ['year', 'years'],
        ][$this->frequency] ?? ['month', 'months'];

        $every = $n === 1
            ? 'Every '.$unit[0]
            : "Every {$n} ".$unit[1];

        return $every.' since '.$this->starts_on->format('d M Y');
    }

    /**
     * The run date after $from, keeping monthly-style schedules anchored to the
     * start day where possible (e.g. starting on the 31st clamps to month end).
     */
    public function runAfter(Carbon $from): Carbon
    {
        $n = max(1, (int) $this->interval_count);
        $next = $from->copy();

        return match ($this->frequency) {
            'daily'       => $next->addDays($n),
            'weekly'      => $next->addWeeks($n),
            'quarterly'   => $next->addMonthsNoOverflow(3 * $n),
            'half_yearly' => $next->addMonthsNoOverflow(6 * $n),
            'yearly'      => $next->addYearsNoOverflow($n),
            default       => $next->addMonthsNoOverflow($n),
        };
    }

    /** No more documents should be produced when the end conditions are met. */
    public function isExhausted(): bool
    {
        if ($this->ends_on && $this->next_run_on->gt($this->ends_on)) {
            return true;
        }

        return $this->max_occurrences !== null
            && (int) $this->generated_count >= (int) $this->max_occurrences;
    }

    public function estimatedTotal(): float
    {
        return (float) ($this->template['estimated_total'] ?? 0);
    }
}
