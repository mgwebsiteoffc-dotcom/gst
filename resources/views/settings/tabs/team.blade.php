@php
    $canManage = auth()->user()->hasRole(['owner', 'admin']);
    $plan = $tenant->subscription?->plan;
    $inviteRoles = ['admin' => 'Admin', 'accountant' => 'Accountant', 'operator' => 'Operator', 'viewer' => 'Viewer'];
@endphp

@if ($canManage)
    <x-card title="Invite a team member"
            subtitle="Creates their login account immediately. {{ $plan?->user_limit ? 'Plan limit: '.$tenant->users()->count().' / '.$plan->user_limit.' users.' : '' }}"
            class="mb-5">
        <form method="POST" action="{{ route('settings.team.invite') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @csrf
            <x-field label="Full name" name="name" required>
                <x-input name="name" value="{{ old('name') }}" placeholder="e.g. Rohan Sharma" required />
            </x-field>

            <x-field label="Email (login)" name="email" required>
                <x-input type="email" name="email" value="{{ old('email') }}" placeholder="rohan@yourbusiness.in" required />
            </x-field>

            <x-field label="Role" name="role" required>
                <x-select name="role">
                    @php $selectedRole = old('role', 'accountant'); @endphp
                    @foreach ($inviteRoles as $v => $label)
                        <option value="{{ $v }}" @selected($selectedRole === $v)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="Password" name="password" hint="Blank = auto-generated, shown once after saving.">
                <x-input type="text" name="password" placeholder="Min 8 characters" autocomplete="off" />
            </x-field>

            <div class="flex items-end">
                <x-btn variant="primary" type="submit" class="w-full">Create account</x-btn>
            </div>
        </form>
    </x-card>
@endif

<x-card title="Team and roles" subtitle="Who can access this business, and what they may do." class="!p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">Member</th>
                    <th class="px-3 font-medium">Role</th>
                    <th class="px-3 font-medium">Email</th>
                    <th class="px-3 font-medium">Joined</th>
                    <th class="px-5 font-medium">Status</th>
                    @if ($canManage)<th class="px-5 font-medium text-right">Actions</th>@endif
                </tr>
            </thead>
            <tbody>
            @foreach ($team as $member)
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                                {{ $member->initials() }}
                            </span>
                            <span class="font-medium text-slate-900">{{ $member->name }}</span>
                            @if ($member->id === auth()->id())
                                <span class="text-xs text-slate-400">(you)</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-3"><x-badge tone="{{ $member->isOwner() ? 'emerald' : 'slate' }}">{{ $member->roleLabel() }}</x-badge></td>
                    <td class="px-3 text-slate-600">{{ $member->email }}</td>
                    <td class="px-3 text-slate-500">{{ $member->created_at?->format('d M Y') }}</td>
                    <td class="px-5">
                        @if ($member->is_active)
                            <x-badge tone="emerald">Active</x-badge>
                        @else
                            <x-badge tone="rose">Deactivated</x-badge>
                        @endif
                    </td>

                    @if ($canManage)
                        <td class="px-5 text-right">
                            @unless ($member->isOwner() || $member->id === auth()->id())
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('settings.team.role', $member->id) }}" class="flex items-center gap-1.5">
                                        @csrf @method('PUT')
                                        <select name="role" class="rounded-lg border-slate-300 py-1 text-xs">
                                            @foreach ($inviteRoles as $v => $label)
                                                <option value="{{ $v }}" @selected($member->hasRole($v))>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="text-xs font-medium text-indigo-600 hover:underline">Save</button>
                                    </form>

                                    <form method="POST" action="{{ route('settings.team.toggle', $member->id) }}"
                                          onsubmit="return confirm('{{ $member->is_active ? 'Deactivate' : 'Reactivate' }} {{ $member->name }}?')">
                                        @csrf @method('PUT')
                                        <button class="text-xs font-medium {{ $member->is_active ? 'text-rose-600' : 'text-emerald-600' }} hover:underline">
                                            {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            @endunless
                        </td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>

<x-card title="What each role can do" class="mt-5">
    <div class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            'Owner' => 'Full control, including billing, team management and deleting records.',
            'Admin' => 'Runs day-to-day operations and compliance. No billing or ownership changes.',
            'Accountant' => 'Invoices, payments, expenses and GST filings. Cannot change company setup.',
            'Operator' => 'Raises invoices and records payments at the counter. Nothing else.',
            'Viewer' => 'Read-only access — ideal for an external CA or auditor.',
        ] as $role => $desc)
            <div class="rounded-lg border border-slate-200 p-3">
                <p class="font-medium text-slate-800">{{ $role }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ $desc }}</p>
            </div>
        @endforeach
    </div>
</x-card>
