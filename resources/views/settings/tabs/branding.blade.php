<form method="POST" action="{{ route('branding.update') }}" enctype="multipart/form-data"
      x-data="{ accent: @js($tenant->branding['accent_color'] ?? '#006FD1') }">
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
                    <button type="submit" formaction="{{ route('branding.remove', $key) }}" formmethod="POST"
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
