<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use BelongsToTenant;

    protected $guarded = [];
    protected function casts(): array { return ['is_default' => 'boolean']; }
}
