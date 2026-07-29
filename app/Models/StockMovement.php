<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use BelongsToTenant;

    protected $guarded = [];
    protected function casts(): array { return ['moved_on' => 'date']; }

    public function item() { return $this->belongsTo(Item::class); }
    public function source() { return $this->morphTo(); }
}
