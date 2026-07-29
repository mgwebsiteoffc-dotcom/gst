<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $guarded = [];
    protected $attributes = ['active' => true, 'position' => 0, 'page' => 'home'];

    protected function casts(): array { return ['active' => 'boolean']; }

    public function scopeForPage($q, string $page)
    {
        return $q->where('page', $page)->where('active', true)->orderBy('position');
    }
}
