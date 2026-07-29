<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class EwayBill extends Model
{
    use BelongsToTenant;

    protected $table = 'eway_bills';
    protected $guarded = [];
    protected function casts(): array
    {
        return ['response_payload' => 'array', 'ewb_date' => 'datetime', 'valid_until' => 'datetime'];
    }

    public function document() { return $this->belongsTo(Document::class); }
}
