<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $guarded = [];

    protected $hidden = ['p256dh', 'auth'];

    public function user() { return $this->belongsTo(User::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
}
