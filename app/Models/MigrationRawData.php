<?php
namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MigrationRawData extends Model
{
    use BelongsToTenant;
    protected $guarded = [];
    protected function casts(): array { return ['data' => 'array']; }

    public function importJob() { return $this->belongsTo(ImportJob::class); }
    public function mappedRecord() { return $this->morphTo(null, 'mapped_model', 'mapped_id'); }

    public function get(string $key, $default = null) { return data_get($this->data, $key, $default); }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('section', 'like', "%{$term}%")
              ->orWhereRaw('JSON_SEARCH(data, "one", ?, NULL) IS NOT NULL', ["%{$term}%"]);
        });
    }

    public function scopeOfSection($query, string $section) { return $query->where('section', $section); }
}