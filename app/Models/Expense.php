<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use BelongsToTenant;

    protected $guarded = [];
    protected function casts(): array { return ['spent_on' => 'date', 'itc_eligible' => 'boolean']; }

    public function party() { return $this->belongsTo(Party::class); }
}
