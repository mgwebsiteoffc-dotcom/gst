<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    public const TYPES = [
        'invoice' => 'Tax Invoice',
        'bill_of_supply' => 'Bill of Supply',
        'quotation' => 'Quotation',
        'proforma' => 'Proforma Invoice',
        'delivery_challan' => 'Delivery Challan',
        'credit_note' => 'Credit Note',
        'debit_note' => 'Debit Note',
        'purchase_order' => 'Purchase Order',
        'purchase_bill' => 'Purchase Bill',
    ];

    protected $guarded = [];

    protected $attributes = [
        'tax_mode' => 'gst',
        'status' => 'draft',
        'currency' => 'INR',
        'exchange_rate' => 1,
        'round_off_enabled' => true,
        'source' => 'manual',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'meta' => 'array',
            'is_interstate' => 'boolean',
            'reverse_charge' => 'boolean',
            'is_export' => 'boolean',
            'with_payment_of_tax' => 'boolean',
            'round_off_enabled' => 'boolean',
        ];
    }

    public function lines() { return $this->hasMany(DocumentLine::class)->orderBy('position'); }
    public function party() { return $this->belongsTo(Party::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function einvoice() { return $this->hasOne(Einvoice::class); }
    public function ewayBill() { return $this->hasOne(EwayBill::class); }
    public function parent() { return $this->belongsTo(Document::class, 'parent_id'); }
    public function children() { return $this->hasMany(Document::class, 'parent_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeSales($q) { return $q->whereIn('doc_type', ['invoice', 'bill_of_supply', 'credit_note', 'debit_note']); }
    public function scopeGst($q) { return $q->where('tax_mode', 'gst'); }
    public function scopeNonGst($q) { return $q->where('tax_mode', 'non_gst'); }

    public function isGst(): bool { return ($this->tax_mode ?? 'gst') === 'gst'; }
    public function scopeOfType($q, string $type) { return $q->where('doc_type', $type); }
    public function scopeBetween($q, $from, $to) { return $q->whereBetween('issue_date', [$from, $to]); }

    public function getTitleAttribute(): string
    {
        // A plain invoice must not be titled "Tax Invoice" — that wording is
        // reserved for documents that actually carry GST.
        if (! $this->isGst() && $this->doc_type === 'invoice') {
            return 'Invoice';
        }

        return self::TYPES[$this->doc_type] ?? 'Document';
    }
    public function getTaxTotalAttribute(): float
    {
        return (float) $this->cgst_total + (float) $this->sgst_total + (float) $this->igst_total + (float) $this->cess_total;
    }
    public function getAmountInWordsAttribute(): string { return Money::inWords((float) $this->grand_total, $this->currency); }

    public function isEinvoiceApplicable(): bool
    {
        // Non-GST documents are outside the e-invoicing system entirely.
        if (! $this->isGst()) {
            return false;
        }
        if (! in_array($this->doc_type, ['invoice', 'credit_note', 'debit_note'], true)) {
            return false;
        }
        if (! $this->tenant?->einvoice_enabled) {
            return false;
        }

        // e-Invoice applies to B2B, SEZ, deemed-export and export documents only.
        return $this->is_export || in_array($this->supply_type, ['B2B', 'SEZ', 'DEEMED_EXPORT'], true);
    }

    public function affectsStock(): bool
    {
        return in_array($this->doc_type, ['invoice', 'bill_of_supply', 'purchase_bill', 'delivery_challan', 'credit_note'], true);
    }

    /** HSN-wise summary used in printed invoices and GSTR-1 table 12. */
    public function hsnSummary(): array
    {
        return $this->lines->groupBy('hsn_sac')->map(fn ($lines, $hsn) => [
            'hsn' => $hsn ?: '-',
            'uqc' => $lines->first()->uqc,
            'quantity' => (float) $lines->sum('quantity'),
            'taxable_value' => (float) $lines->sum('taxable_value'),
            'cgst' => (float) $lines->sum('cgst_amount'),
            'sgst' => (float) $lines->sum('sgst_amount'),
            'igst' => (float) $lines->sum('igst_amount'),
            'cess' => (float) $lines->sum('cess_amount'),
        ])->values()->all();
    }
}
