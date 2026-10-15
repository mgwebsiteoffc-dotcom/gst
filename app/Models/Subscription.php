<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Subscription extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'current_period_end' => 'datetime',
            'started_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function plan() { return $this->belongsTo(Plan::class); }

    /**
     * The live subscription for a business, plan eager loaded.
     *
     * Query-based on purpose: lazy loading is blocked outside production, and
     * a branch must be able to read the MAIN business's subscription without
     * tripping that guard.
     */
    public static function forTenant(?int $tenantId): ?self
    {
        if (! $tenantId) {
            return null;
        }

        return static::withoutGlobalScope('tenant')
            ->with('plan')
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();
    }
    public function isActive(): bool { return in_array($this->status, ['active', 'trialing'], true); }

    /**
     * Drop attributes whose columns do not exist yet.
     *
     * The lifecycle columns (started_at / canceled_at) arrived in a later
     * migration. If code is deployed before `php artisan migrate` runs, an
     * update would otherwise fail with SQLSTATE 42S22 — this keeps checkout,
     * cancel and admin plan changes working on an older database.
     */
    public static function existingAttributes(array $attributes): array
    {
        $columns = array_flip(Schema::getColumnListing((new static)->getTable()));

        return array_intersect_key($attributes, $columns);
    }
}
