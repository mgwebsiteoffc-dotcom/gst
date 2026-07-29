<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralClick extends Model
{
    protected $guarded = [];

    public function partner() { return $this->belongsTo(Partner::class); }
}
