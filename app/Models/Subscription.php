<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use BelongsToTenant;

    protected $guarded = [];
    protected function casts(): array { return ['current_period_end' => 'datetime']; }

    public function plan() { return $this->belongsTo(Plan::class); }
    public function isActive(): bool { return in_array($this->status, ['active', 'trialing'], true); }
}
