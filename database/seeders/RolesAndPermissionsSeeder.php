<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /** Every capability in the app, grouped by area. */
    public const PERMISSIONS = [
        'invoices' => ['invoice.view', 'invoice.create', 'invoice.update', 'invoice.delete', 'invoice.cancel'],
        'documents' => ['quotation.manage', 'purchase.manage', 'delivery_challan.manage', 'credit_note.manage'],
        'records' => ['party.view', 'party.manage', 'item.view', 'item.manage'],
        'money' => ['payment.record', 'expense.manage'],
        'compliance' => ['einvoice.generate', 'einvoice.cancel', 'ewaybill.generate', 'gstr.view', 'gstr.export'],
        'reports' => ['report.view', 'stock.view', 'stock.adjust'],
        'setup' => ['settings.view', 'settings.update', 'branding.update', 'shopify.manage'],
        'team' => ['user.view', 'user.invite', 'user.manage'],
        'billing' => ['subscription.view', 'subscription.manage'],
    ];

    /** Tenant-level roles and what each may do. */
    public const ROLES = [
        // Business owner — everything inside their own tenant.
        'owner' => ['*'],

        // Day-to-day manager: no billing or destructive team changes.
        'admin' => [
            'invoice.view', 'invoice.create', 'invoice.update', 'invoice.cancel',
            'quotation.manage', 'purchase.manage', 'delivery_challan.manage', 'credit_note.manage',
            'party.view', 'party.manage', 'item.view', 'item.manage',
            'payment.record', 'expense.manage',
            'einvoice.generate', 'einvoice.cancel', 'ewaybill.generate', 'gstr.view', 'gstr.export',
            'report.view', 'stock.view', 'stock.adjust',
            'settings.view', 'branding.update', 'shopify.manage',
            'user.view', 'user.invite',
        ],

        // Books and filings, but cannot change company setup.
        'accountant' => [
            'invoice.view', 'invoice.create', 'invoice.update',
            'quotation.manage', 'purchase.manage', 'credit_note.manage',
            'party.view', 'party.manage', 'item.view',
            'payment.record', 'expense.manage',
            'einvoice.generate', 'ewaybill.generate', 'gstr.view', 'gstr.export',
            'report.view', 'stock.view',
            'settings.view',
        ],

        // Front-desk billing: raise invoices, take payments, nothing else.
        'operator' => [
            'invoice.view', 'invoice.create',
            'quotation.manage', 'delivery_challan.manage',
            'party.view', 'party.manage', 'item.view',
            'payment.record',
            'stock.view',
        ],

        // Read-only, e.g. an external CA or auditor.
        'viewer' => [
            'invoice.view', 'party.view', 'item.view',
            'gstr.view', 'report.view', 'stock.view', 'settings.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = collect(self::PERMISSIONS)->flatten()->all();

        foreach ($all as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // The platform-wide super admin lives outside any tenant, so its role
        // is created with a null team id.
        setPermissionsTeamId(null);
        $super = Role::findOrCreate('super-admin', 'web');
        $super->syncPermissions($all);

        // Tenant roles are per-business (spatie "teams" keyed on tenant_id).
        Tenant::query()->each(function (Tenant $tenant) use ($all) {
            $this->syncRolesFor($tenant, $all);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Create (or refresh) the standard role set for one tenant. */
    public static function syncRolesFor(Tenant $tenant, ?array $all = null): void
    {
        $all ??= collect(self::PERMISSIONS)->flatten()->all();

        setPermissionsTeamId($tenant->id);

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role, 'web')
                ->syncPermissions($permissions === ['*'] ? $all : $permissions);
        }
    }
}
