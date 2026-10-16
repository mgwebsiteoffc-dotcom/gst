<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Invoice / bill line items.
 *
 * The table has no tenant_id (lines always belong to a document), so instead
 * of the BelongsToTenant trait this model constrains itself to documents of
 * the active business. It is defence in depth: today lines are only ever read
 * through a scoped Document or by document_id, but a future
 * DocumentLine::find($id) would otherwise cross tenants.
 */
class DocumentLine extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'rate' => 'decimal:4', 'gst_rate' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $q) {
            if ($id = TenantContext::id()) {
                $q->whereIn('document_id', Document::query()
                    ->withoutGlobalScope('tenant')
                    ->where('tenant_id', $id)
                    ->select('id'));
            }
        });
    }

    public function document() { return $this->belongsTo(Document::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
