<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    protected $guarded = [];
    protected $attributes = ['status' => 'trial', 'lifetime_value_paise' => 0];

    protected function casts(): array { return ['converted_at' => 'datetime']; }

    public function partner() { return $this->belongsTo(Partner::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function commissions() { return $this->hasMany(Commission::class); }
}
