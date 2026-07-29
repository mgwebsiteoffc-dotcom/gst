<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Demo accounts — every password is "password".
     * Roles come from RolesAndPermissionsSeeder, which must run first.
     */
    public const ACCOUNTS = [
        // Platform staff (no tenant).
        ['name' => 'Platform Owner', 'email' => 'superadmin@bikribook.test', 'role' => null, 'tenant' => null, 'super' => true],

        // Demo Traders — the GST business.
        ['name' => 'Demo Owner',      'email' => 'owner@demo.test',      'role' => 'owner',      'tenant' => 'demo'],
        ['name' => 'Rahul Admin',     'email' => 'admin@demo.test',      'role' => 'admin',      'tenant' => 'demo'],
        ['name' => 'Priya Accountant','email' => 'accountant@demo.test', 'role' => 'accountant', 'tenant' => 'demo'],
        ['name' => 'Sunil Counter',   'email' => 'operator@demo.test',   'role' => 'operator',   'tenant' => 'demo'],
        ['name' => 'External CA',     'email' => 'viewer@demo.test',     'role' => 'viewer',     'tenant' => 'demo'],

        // Anita Design Studio — the non-GST freelancer.
        ['name' => 'Anita',           'email' => 'anita@demo.test',      'role' => 'owner',      'tenant' => 'freelance'],
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as $account) {
            $tenant = $account['tenant'] ? Tenant::where('slug', $account['tenant'])->first() : null;

            if ($account['tenant'] && ! $tenant) {
                continue;
            }

            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'tenant_id' => $tenant?->id,
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'is_super_admin' => $account['super'] ?? false,
                    'email_verified_at' => now(),
                ]
            );

            // Roles are team-scoped, so set the team before assigning.
            setPermissionsTeamId($tenant?->id);

            if ($account['super'] ?? false) {
                $user->syncRoles(['super-admin']);
            } elseif ($account['role']) {
                $user->syncRoles([$account['role']]);
            }
        }

        setPermissionsTeamId(null);

        $this->command?->newLine();
        $this->command?->table(
            ['Email', 'Password', 'Role', 'Business'],
            collect(self::ACCOUNTS)->map(fn ($a) => [
                $a['email'], 'password',
                ($a['super'] ?? false) ? 'super-admin' : $a['role'],
                $a['tenant'] ?? '—',
            ])->all()
        );
    }
}
