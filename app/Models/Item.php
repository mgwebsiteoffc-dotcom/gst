<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price_inclusive_tax' => 'boolean',
            'track_inventory' => 'boolean',
            'sale_price' => 'decimal:4',
            'purchase_price' => 'decimal:4',
            'gst_rate' => 'decimal:2',
        ];
    }

    public function movements() { return $this->hasMany(StockMovement::class); }

    public function stockOnHand(?int $warehouseId = null): float
    {
        $q = $this->movements();
        if ($warehouseId) $q->where('warehouse_id', $warehouseId);
        $in = (clone $q)->where('direction', 'in')->sum('quantity');
        $out = (clone $q)->where('direction', 'out')->sum('quantity');
        return (float) $this->opening_stock + (float) $in - (float) $out;
    }

    public function isLowStock(): bool
    {
        return $this->track_inventory && $this->stockOnHand() <= (float) $this->reorder_level;
    }
}
