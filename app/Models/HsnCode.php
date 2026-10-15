<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HsnCode extends Model
{
    protected $table = 'hsn_codes';

    protected $fillable = ['code', 'description', 'type', 'gst_rate'];

    protected $casts = [
        'gst_rate' => 'decimal:2',
    ];

    public function scopeHsn($query)
    {
        return $query->where('type', 'HSN');
    }

    public function scopeSac($query)
    {
        return $query->where('type', 'SAC');
    }
}
