<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $q) {
            if ($id = TenantContext::id()) {
                $q->where($q->getModel()->getTable().'.tenant_id', $id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->tenant_id) && ($id = TenantContext::id())) {
                $model->tenant_id = $id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
