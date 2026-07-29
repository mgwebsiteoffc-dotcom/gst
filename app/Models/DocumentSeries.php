<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class DocumentSeries extends Model
{
    use BelongsToTenant;

    protected $table = 'document_series';
    protected $guarded = [];

    /** Mirrors schema defaults so a newly created series pads correctly. */
    protected $attributes = [
        'next_number' => 1,
        'padding' => 4,
        'prefix' => '',
        'is_default' => true,
    ];

    protected function casts(): array
    {
        return ['next_number' => 'integer', 'padding' => 'integer', 'is_default' => 'boolean'];
    }
}
