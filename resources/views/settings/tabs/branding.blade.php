<form method="POST" action="{{ route('branding.update') }}" enctype="multipart/form-data"
      x-data="{ accent: @js($tenant->branding['accent_color'] ?? '#4f46e5') }">
    @csrf
    {{-- Preserve fields owned by other tabs. --}}
    <input type="hidden" name="name" value="{{ $tenant->name }}">
    <input type="hidden" name="registration_type" value="{{ $tenant->registration_type }}">

    <div class="grid gap-5 lg:grid-cols-2">
        <x-card title="Logo and signature" subtitle="PNG or JPG. The signature prints above the authorised-signatory line.">
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach (['logo' => ['Company logo', 'Max 1 MB · shown top-left'], 'signature' => ['Signature', 'Max 512 KB · transparent PNG works best']] as $key => [$label, $hint])
                    <div>
                        <p class="mb-1 text-sm font-medium text-slate-700">{{ $label }}</p>
                        <p class="mb-2 text-xs text-slate-500">{{ $hint }}</p>

                        <div class="flex h-28 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3">
                            @if (!empty($tenant->branding[$key]))
                                <img src="{{ Storage::url($tenant->branding[$key]) }}" alt="{{ $label }}" class="max-h-24 object-contain">
                            @else
                                <span class="text-xs text-slate-400">No {{ strtolower($label) }} uploaded</span>
                            @endif
                        </div>

                        <input type="file" name="{{ $key }}" accept="image/png,image/jpeg,image/webp"
                               class="mt-2 w-full text-xs file:mr-2 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                    </div>
                @endforeach
            </div>

            <div class="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                <x-field label="Accent colour" name="accent_color" hint="Used across the app and invoice headings.">
                    <div class="flex items-center gap-2">
                        <input type="color" name="accent_color" x-model="accent" class="h-9 w-12 cursor-pointer rounded border border-slate-300">
                        <x-input x-model="accent" class="!w-32 font-mono" />
                    </div>
                </x-field>

 <x-field label="PDF template" name="pdf_template">
                    <x-select name="pdf_template">
                        @foreach (['classic' => 'Classic', 'modern' => 'Modern', 'compact' => 'Compact'] as $v => $t)
                            <option value="{{ $v }}" @selected($tenant->setting('pdf_template', 'classic') === $v)>{{ $t }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            </div>

             {{-- Invoice Options --}}
            <div class="mt-4 border-t border-slate-100 pt-4">
                <p class="mb-3 text-sm font-medium text-slate-700">Invoice Options</p>
                <label class="flex items-center gap-3 cursor-pointer">
                    {{-- Sentinel: posts 0 when the box is unticked, so the controller can
                         tell "unticked" apart from "this form doesn't own the setting". --}}
                    <input type="hidden" name="show_ship_to" value="0">
                    <input type="checkbox" name="show_ship_to" value="1"
                           @checked($tenant->setting('show_ship_to', false))
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <div>
                        <span class="text-sm font-medium text-slate-800">Show "Ship To" address</span>
                        <p class="text-xs text-slate-500">Enable for product invoices. Disable for service-only businesses.</p>
                    </div>
                </label>
            </div>



          {{-- Template Preview Buttons --}}
            <div class="mt-4 border-t border-slate-100 pt-4">
                <p class="mb-2 text-sm font-medium text-slate-700">Preview Invoice PDF</p>
                <p class="mb-3 text-xs text-slate-500">See how your invoice looks with current branding. Opens in a new tab.</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ([
                        'classic' => ['Classic', 'Bordered, traditional GST format'],
                        'modern' => ['Modern', 'Clean header bar, minimal borders'],
                        'compact' => ['Compact', 'Dense layout for many line items'],
                    ] as $tpl => [$label, $desc])
                        <a href="{{ route('branding.preview', ['template' => $tpl]) }}" target="_blank"
                           class="flex items-center gap-3 rounded-lg border px-3 py-2 transition
                                  {{ $tenant->setting('pdf_template', 'classic') === $tpl ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                            <div class="flex h-10 w-8 items-center justify-center rounded border border-slate-300 bg-white text-xs text-slate-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-slate-800">{{ $label }}</div>
                                <div class="text-xs text-slate-500">{{ $desc }}</div>
                            </div>
                            <svg class="ml-2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                        </a>
                    @endforeach
                </div>
            </div>
        </x-card>


        <x-card title="Preview" subtitle="Roughly how your invoice header will look.">
            <div class="rounded-lg border border-slate-200 p-5">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-3">
                    <div>
                        @if (!empty($tenant->branding['logo']))
                            <img src="{{ Storage::url($tenant->branding['logo']) }}" class="mb-2 max-h-12 object-contain">
                        @endif
                        <p class="font-semibold text-slate-900">{{ $tenant->legal_name ?: $tenant->name }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $tenant->setting('address.line1') }}{{ $tenant->setting('address.city') ? ', '.$tenant->setting('address.city') : '' }}<br>
                            @if ($tenant->gstin) GSTIN: {{ $tenant->gstin }} @endif
                        </p>
                    </div>
                    <div class="text-right text-xs text-slate-500">
                        <p class="text-sm font-semibold" :style="`color:${accent}`">
                            {{ $tenant->tax_mode === 'gst' ? 'TAX INVOICE' : 'INVOICE' }}
                        </p>
                        <p class="mt-1">{{ $tenant->tax_mode === 'gst' ? 'INV' : 'BILL' }}/2026-27/0001</p>
                        <p>{{ now()->format('d-m-Y') }}</p>
                    </div>
                </div>

                <div class="flex items-end justify-end pt-8">
                    <div class="text-center text-xs text-slate-500">
                        <p>For {{ $tenant->legal_name ?: $tenant->name }}</p>
                        @if (!empty($tenant->branding['signature']))
                            <img src="{{ Storage::url($tenant->branding['signature']) }}" class="mx-auto my-1 max-h-12 object-contain">
                        @else
                            <div class="h-10"></div>
                        @endif
                        <p class="border-t border-slate-300 pt-1">Authorised Signatory</p>
                    </div>
                </div>
            </div>

            @foreach (['logo', 'signature'] as $key)
                @if (!empty($tenant->branding[$key]))
                    <button type="submit" formaction="{{ route('branding.remove', $key) }}?_method=DELETE" formmethod="POST"
                            onclick="return confirm('Remove the {{ $key }}?')"
                            class="mt-3 mr-3 text-xs text-rose-600 hover:underline">Remove {{ $key }}</button>
                @endif
            @endforeach
        </x-card>
    </div>

    <div class="mt-5 flex justify-end">
        <x-btn variant="primary" type="submit">Save branding</x-btn>
    </div>
</form>
