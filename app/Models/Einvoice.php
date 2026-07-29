<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Einvoice extends Model
{
    use BelongsToTenant;

    protected $guarded = [];
    protected function casts(): array
    {
        return ['request_payload' => 'array', 'response_payload' => 'array', 'ack_date' => 'datetime'];
    }

    public function document() { return $this->belongsTo(Document::class); }
    public function isGenerated(): bool { return $this->status === 'generated'; }
}
