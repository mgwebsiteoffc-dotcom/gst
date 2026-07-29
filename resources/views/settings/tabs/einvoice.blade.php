<div class="grid gap-5 lg:grid-cols-2">
    <x-card title="e-Invoice gateway">
        <x-slot:actions>
            <x-badge tone="slate">{{ $gateway }}</x-badge>
        </x-slot:actions>

        <p class="mb-4 text-sm text-slate-500">
            @if ($tenant->nic_verified_at)
                <span class="font-medium text-emerald-600">Verified {{ $tenant->nic_verified_at->diffForHumans() }}.</span>
            @else
                Not verified yet. Save your credentials, then run a test.
            @endif
        </p>

        <form method="POST" action="{{ route('settings.nic') }}" class="space-y-4">
            @csrf @method('PUT')

            <x-field label="Gateway" name="einvoice_driver">
                <x-select name="einvoice_driver">
                    <option value="sandbox" @selected($tenant->einvoice_driver === 'sandbox')>Offline sandbox — no network calls</option>
                    <option value="nic" @selected($tenant->einvoice_driver === 'nic')>NIC / GSP — live IRP</option>
                </x-select>
            </x-field>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="nic_sandbox" value="1" @checked($tenant->nic_sandbox)
                       class="rounded border-slate-300 text-indigo-600">
                Use the NIC sandbox endpoint (uncheck for production)
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="NIC username" name="nic_username">
                    <x-input name="nic_username" placeholder="{{ $tenant->nic_username ? '••••••••' : '' }}" />
                </x-field>
                <x-field label="NIC password" name="nic_password">
                    <x-input type="password" name="nic_password" placeholder="{{ $tenant->nic_password ? '••••••••' : '' }}" />
                </x-field>
                <x-field label="GSP client ID" name="nic_client_id">
                    <x-input name="nic_client_id" placeholder="{{ $tenant->nic_client_id ? '••••••••' : '' }}" />
                </x-field>
                <x-field label="GSP client secret" name="nic_client_secret">
                    <x-input type="password" name="nic_client_secret" placeholder="{{ $tenant->nic_client_secret ? '••••••••' : '' }}" />
                </x-field>
            </div>

            <p class="text-xs text-slate-400">Leave a field blank to keep the saved value. Credentials are encrypted at rest.</p>

            <div class="flex gap-5 border-t border-slate-100 pt-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="einvoice_enabled" value="1" @checked($tenant->einvoice_enabled)
                           class="rounded border-slate-300 text-indigo-600"> Enable e-Invoice
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="ewaybill_enabled" value="1" @checked($tenant->ewaybill_enabled)
                           class="rounded border-slate-300 text-indigo-600"> Enable e-Way Bill
                </label>
            </div>

            <div class="flex justify-end">
                <x-btn variant="primary" type="submit">Save credentials</x-btn>
            </div>
        </form>
    </x-card>

    <x-card title="Test the connection" subtitle="Performs a real authentication call against the IRP.">
        <p class="text-sm text-slate-500">
            Sandbox mode is skipped rather than faked, so a green result always means a genuine NIC handshake succeeded.
        </p>

        <form method="POST" action="{{ route('settings.nic.test') }}" class="mt-4">
            @csrf
            <x-btn type="submit">Run connection test</x-btn>
        </form>

        <div class="mt-5 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
            <p class="mb-1 font-medium text-slate-700">From the command line</p>
            <code class="block">php artisan einvoice:test {{ $tenant->slug }}</code>
        </div>
    </x-card>
</div>
