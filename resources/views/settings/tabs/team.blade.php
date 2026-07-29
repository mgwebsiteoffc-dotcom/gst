<x-card title="Team and roles" subtitle="Who can access this business, and what they may do." class="!p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">Member</th>
                    <th class="px-3 font-medium">Role</th>
                    <th class="px-3 font-medium">Email</th>
                    <th class="px-5 font-medium">Joined</th>
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
                        </div>
                    </td>
                    <td class="px-3"><x-badge tone="{{ $member->isOwner() ? 'emerald' : 'slate' }}">{{ $member->roleLabel() }}</x-badge></td>
                    <td class="px-3 text-slate-600">{{ $member->email }}</td>
                    <td class="px-5 text-slate-500">{{ $member->created_at?->format('d M Y') }}</td>
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
