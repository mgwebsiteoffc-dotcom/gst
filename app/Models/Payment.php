<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToTenant;

    protected $guarded = [];
    protected function casts(): array { return ['paid_on' => 'date']; }

    public function document() { return $this->belongsTo(Document::class); }
    public function party() { return $this->belongsTo(Party::class); }
}
