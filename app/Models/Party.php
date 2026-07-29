<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Gstin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $guarded = [];
    protected function casts(): array { return ['shipping_address' => 'array']; }

    public function documents() { return $this->hasMany(Document::class); }
    public function payments() { return $this->hasMany(Payment::class); }

    public function scopeCustomers($q) { return $q->whereIn('type', ['customer', 'both']); }
    public function scopeVendors($q) { return $q->whereIn('type', ['vendor', 'both']); }

    public function resolvedStateCode(): ?string
    {
        return $this->billing_state_code ?: Gstin::stateCode($this->gstin);
    }

    public function isRegistered(): bool
    {
        return in_array($this->gst_treatment, ['regular', 'composition', 'sez'], true) && (bool) $this->gstin;
    }

    /** Outstanding receivable across unpaid invoices. */
    public function outstanding(): float
    {
        return (float) $this->documents()
            ->whereIn('doc_type', ['invoice', 'bill_of_supply'])
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->sum('balance_due');
    }
}
