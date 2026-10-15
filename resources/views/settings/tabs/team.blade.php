@php
    $home = $tenant->parent_tenant_id ? ($tenant->parent ?: $tenant) : $tenant;
    $plan = $home->subscription?->plan;
    $branches = $home->branches()->orderBy('name')->get();
    $multiBranch = $plan?->includes('multi_branch');
    $userLimit = $plan?->user_limit ?? 1;
    $teamCount = $team->count();
    $canManage = auth()->user()->hasAnyRole(['owner', 'admin']) && (int) auth()->user()->tenant_id === (int) $home->id;
@endphp

@if ($canManage)
    <x-card :title="__('Add team member')" class="mb-5"
            :subtitle="__('Create a login and choose which branch they work in.')">
        @if ($teamCount >= $userLimit)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ __('Your :plan plan includes :limit user(s). Upgrade to add more.', ['plan' => $plan?->name ?? __('Free'), 'limit' => $userLimit]) }}
                <a href="{{ route('billing.plans') }}" class="font-medium underline">{{ __('View plans') }}</a>
            </div>
        @else
            <form method="POST" action="{{ route('team.store') }}" class="grid gap-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field :label="__('Full name')" name="name" required>
                        <x-input name="name" value="{{ old('name') }}" required />
                    </x-field>
                    <x-field :label="__('Email address')" name="email" required>
                        <x-input type="email" name="email" value="{{ old('email') }}" required />
                    </x-field>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field :label="__('Password')" name="password" required :hint="__('8+ characters, letters and numbers.')">
                        <x-input type="password" name="password" required autocomplete="new-password" />
                    </x-field>
                    <x-field :label="__('Confirm password')" name="password_confirmation" required>
                        <x-input type="password" name="password_confirmation" required autocomplete="new-password" />
                    </x-field>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field :label="__('Phone')" name="phone">
                        <x-input name="phone" value="{{ old('phone') }}" />
                    </x-field>
                    <x-field :label="__('Role')" name="role" required>
                        <x-select name="role">
                            @foreach (['admin' => __('Admin'), 'accountant' => __('Accountant'), 'operator' => __('Operator'), 'viewer' => __('Viewer')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field :label="__('Branch')" name="branch_id"
                             :hint="$multiBranch ? null : __('Multi-branch is not in your plan.')">
                        <x-select name="branch_id" @disabled(! $multiBranch || $branches->isEmpty())>
                            <option value="">{{ $home->name }} ({{ __('Head office') }})</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) old('branch_id') === (int) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                </div>

                @if (! $multiBranch)
                    <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        {{ __('Want this person in a branch?') }}
                        <a href="{{ route('billing.plans') }}" class="font-medium text-indigo-600 hover:underline">{{ __('Upgrade your plan for multi-branch') }}</a>
                    </p>
                @endif

                <div><x-btn variant="primary" type="submit">{{ __('Create user') }}</x-btn></div>
            </form>
        @endif
    </x-card>
@endif

<x-card :title="__('Team and roles')" :subtitle="__('Who can access this business, and what they may do.')" class="!p-0">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[680px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                    <th class="px-3 font-medium">{{ __('Role') }}</th>
                    <th class="px-3 font-medium">{{ __('Email address') }}</th>
                    <th class="px-3 font-medium">{{ __('Branch') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ $teamCount }} / {{ $userLimit }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($team as $member)
                @php
                    $memberTenant = $member->tenant_id === $home->id ? $home : $branches->firstWhere('id', $member->tenant_id);
                    setPermissionsTeamId($member->tenant_id);
                    $memberRoles = $member->getRoleNames();
                    setPermissionsTeamId(\App\Support\TenantContext::id());
                @endphp
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                                {{ $member->initials() }}
                            </span>
                            <span class="font-medium text-slate-900">{{ $member->name }}</span>
                        </div>
                    </td>
                    <td class="px-3">
                        @if ($canManage && ! $member->isOwner())
                            <form method="POST" action="{{ route('team.update', $member) }}" class="inline-flex items-center gap-1.5">
                                @csrf @method('PUT')
                                <x-select name="role" class="!w-32 !py-1 text-xs" onchange="this.form.submit()">
                                    @foreach (['admin' => __('Admin'), 'accountant' => __('Accountant'), 'operator' => __('Operator'), 'viewer' => __('Viewer')] as $value => $label)
                                        <option value="{{ $value }}" @selected($memberRoles->contains($value))>{{ $label }}</option>
                                    @endforeach
                                </x-select>
                            </form>
                        @else
                            <x-badge tone="{{ $member->isOwner() ? 'emerald' : 'slate' }}">{{ $member->roleLabel() }}</x-badge>
                        @endif
                    </td>
                    <td class="px-3 text-slate-600">{{ $member->email }}</td>
                    <td class="px-3 text-slate-600">
                        {{ $memberTenant?->name ?? '—' }}
                        @if ($memberTenant && $memberTenant->id !== $home->id)
                            <x-badge tone="slate" class="ml-1">{{ __('Branch') }}</x-badge>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if ($canManage && ! $member->isOwner() && (int) auth()->id() !== (int) $member->id)
                            <form method="POST" action="{{ route('team.destroy', $member) }}" class="inline"
                                  onsubmit="return confirm('{{ __('Remove this user?') }}')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-rose-600 hover:underline">{{ __('Remove') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>

<x-card :title="__('What each role can do')" class="mt-5">
    <div class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            __('Owner') => __('Full control, including billing, team management and deleting records.'),
            __('Admin') => __('Runs day-to-day operations and compliance. No billing or ownership changes.'),
            __('Accountant') => __('Invoices, payments, expenses and GST filings. Cannot change company setup.'),
            __('Operator') => __('Raises invoices and records payments at the counter. Nothing else.'),
            __('Viewer') => __('Read-only access — ideal for an external CA or auditor.'),
        ] as $role => $desc)
            <div class="rounded-lg border border-slate-200 p-3">
                <p class="font-medium text-slate-800">{{ $role }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ $desc }}</p>
            </div>
        @endforeach
    </div>
</x-card>
