<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HsnCode extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['gst_rate' => 'decimal:2'];
    }
}
