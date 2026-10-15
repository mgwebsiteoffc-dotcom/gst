<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**
     * In-memory defaults mirroring the schema defaults. Without these, a model
     * created without these keys reports null until it is re-fetched, which would
     * silently flip tax-mode resolution and NIC settings.
     */
    protected $attributes = [
        'tax_mode' => 'gst',
        'allow_both_modes' => true,
        'nic_sandbox' => true,
        'registration_type' => 'regular',
        'country' => 'IN',
        'currency' => 'INR',
        'timezone' => 'Asia/Kolkata',
        'einvoice_enabled' => false,
        'ewaybill_enabled' => false,
        'account_status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'branding' => 'array',
            'settings' => 'array',
            'einvoice_enabled' => 'boolean',
            'ewaybill_enabled' => 'boolean',
            'trial_ends_at' => 'datetime',
            'nic_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'allow_both_modes' => 'boolean',
            'nic_sandbox' => 'boolean',
            'nic_username' => 'encrypted',
            'nic_password' => 'encrypted',
            'nic_client_id' => 'encrypted',
            'nic_client_secret' => 'encrypted',
        ];
    }

    public function users() { return $this->hasMany(User::class); }
    public function documents() { return $this->hasMany(Document::class); }
    public function parties() { return $this->hasMany(Party::class); }
    public function items() { return $this->hasMany(Item::class); }
    public function shopifyStores() { return $this->hasMany(ShopifyStore::class); }
    public function subscription() { return $this->hasOne(Subscription::class)->latestOfMany(); }

    // ---- Multi-branch / multi-store ------------------------------------

    /** The parent business this is a branch of (null for a main business). */
    public function parent() { return $this->belongsTo(Tenant::class, 'parent_tenant_id'); }

    /** Branches / retail stores owned by this business. */
    public function branches() { return $this->hasMany(Tenant::class, 'parent_tenant_id')->orderBy('name'); }

    public function isBranch(): bool { return (bool) $this->parent_tenant_id; }

    /** The tenant that holds the subscription: the root business. */
    public function billingTenant(): self
    {
        return $this->parent_tenant_id ? ($this->parent ?? $this) : $this;
    }

    /** This tenant plus its branches, in display order (main business first). */
    public function businessGroup()
    {
        return $this->parent_tenant_id
            ? collect([$this->parent ?: $this])
            : collect([$this])->merge($this->branches()->get());
    }

    /**
     * Businesses this login may switch between: a staff member attached to a
     * branch sees only that branch; users of the main business see all of it.
     */
    public function switchableFor(User $user)
    {
        if (! $user->tenant_id) {
            return collect();
        }

        return $user->tenant_id === $this->id
            ? $this->businessGroup()
            : collect([$this]);
    }


    public function isComposition(): bool { return $this->registration_type === 'composition'; }

    /** A tenant without a GSTIN can only raise plain invoices. */
    public function defaultTaxMode(): string { return $this->gstin ? ($this->tax_mode ?: 'gst') : 'non_gst'; }
    public function canIssueGst(): bool { return (bool) $this->gstin; }
    public function canIssueNonGst(): bool { return ! $this->gstin || $this->allow_both_modes || $this->tax_mode === 'non_gst'; }

    /** True when this tenant has its own NIC/GSP credentials configured. */
    public function hasNicCredentials(): bool
    {
        return $this->nic_username && $this->nic_password && $this->nic_client_id && $this->nic_client_secret;
    }

    public function nicConfig(): array
    {
        return [
            'driver' => $this->einvoice_driver ?: config('einvoice.driver'),
            'base_url' => $this->nic_sandbox ? config('einvoice.sandbox_url') : config('einvoice.production_url'),
            'client_id' => $this->nic_client_id ?: config('einvoice.client_id'),
            'client_secret' => $this->nic_client_secret ?: config('einvoice.client_secret'),
            'username' => $this->nic_username ?: config('einvoice.username'),
            'password' => $this->nic_password ?: config('einvoice.password'),
            'default_gstin' => $this->gstin,
        ];
    }
    public function setting(string $key, $default = null) { return data_get($this->settings, $key, $default); }

    /**
     * Feature access: a per-business override wins over the plan, so the
     * platform can enable multi-branch for a single customer without changing
     * the global plans.
     */
    public function can(string $feature): bool
    {
        $override = data_get($this->settings, 'capabilities.'.$feature);

        if ($override !== null) {
            return (bool) $override;
        }

        return (bool) $this->billingTenant()->subscription?->plan?->includes($feature);
    }

    /** Branches this business may create (extra allowance when granted). */
    public function branchAllowance(): int
    {
        if (! $this->can('multi_branch')) {
            return 0;
        }

        return 1 + (int) ($this->setting('capabilities.extra_branches') ?? 0);
    }
}
