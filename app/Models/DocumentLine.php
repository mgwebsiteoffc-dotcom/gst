<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLine extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'rate' => 'decimal:4', 'gst_rate' => 'decimal:2'];
    }

    public function document() { return $this->belongsTo(Document::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
