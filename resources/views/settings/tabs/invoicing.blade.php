<div class="grid gap-5 lg:grid-cols-2">
    <form method="POST" action="{{ route('settings.tax-mode') }}">
        @csrf @method('PUT')
        <x-card title="Invoicing mode" subtitle="What a new invoice looks like by default.">
            <div class="space-y-2">
                <label class="flex cursor-pointer gap-3 rounded-lg border p-3 transition {{ $tenant->tax_mode === 'gst' ? 'border-indigo-500 bg-indigo-50' : 'border-slate-300 hover:bg-slate-50' }}">
                    <input type="radio" name="tax_mode" value="gst" class="mt-1" @checked($tenant->tax_mode === 'gst') @disabled(! $tenant->gstin)>
                    <span>
                        <span class="block text-sm font-medium text-slate-800">GST Tax Invoice</span>
                        <span class="block text-xs text-slate-500">HSN/SAC, CGST-SGST-IGST breakup, place of supply, GSTR filing and e-invoicing.</span>
                        @unless ($tenant->gstin)
                            <span class="mt-1 block text-xs text-rose-600">Add a GSTIN under Business details to enable this.</span>
                        @endunless
                    </span>
                </label>

                <label class="flex cursor-pointer gap-3 rounded-lg border p-3 transition {{ $tenant->tax_mode === 'non_gst' ? 'border-indigo-500 bg-indigo-50' : 'border-slate-300 hover:bg-slate-50' }}">
                    <input type="radio" name="tax_mode" value="non_gst" class="mt-1" @checked($tenant->tax_mode === 'non_gst')>
                    <span>
                        <span class="block text-sm font-medium text-slate-800">Normal (Non-GST) Invoice</span>
                        <span class="block text-xs text-slate-500">A plain invoice with no tax columns — for unregistered businesses and exempt supplies.</span>
                    </span>
                </label>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm text-slate-600">
                {{-- Sentinel so unticking the box actually saves "off". --}}
                <input type="hidden" name="allow_both_modes" value="0">
                <input type="checkbox" name="allow_both_modes" value="1" @checked($tenant->allow_both_modes)
                       class="rounded border-slate-300 text-indigo-600">
                Let my team choose the mode on each invoice
            </label>

            <div class="mt-4 flex justify-end">
                <x-btn variant="primary" type="submit">Save mode</x-btn>
            </div>
        </x-card>
    </form>

    <form method="POST" action="{{ route('branding.update') }}">
        @csrf
        <input type="hidden" name="name" value="{{ $tenant->name }}">
        <input type="hidden" name="registration_type" value="{{ $tenant->registration_type }}">
        <input type="hidden" name="pdf_template" value="{{ $tenant->setting('pdf_template', 'classic') }}">

        <x-card title="Terms and notes" subtitle="Pre-filled on every new invoice.">
            <div class="grid gap-4">
                <x-field label="Default terms and conditions" name="default_terms">
                    <textarea name="default_terms" rows="6"
                              class="block w-full rounded-lg border border-slate-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                              placeholder="1. Payment due within 15 days.">{{ old('default_terms', $tenant->setting('default_terms')) }}</textarea>
                </x-field>

                <x-field label="Invoice footer" name="invoice_footer" hint="A short line at the very bottom of the PDF.">
                    <textarea name="invoice_footer" rows="2"
                              class="block w-full rounded-lg border border-slate-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                              placeholder="Thank you for your business!">{{ old('invoice_footer', $tenant->setting('invoice_footer')) }}</textarea>
                </x-field>
            </div>

            <div class="mt-4 flex justify-end">
                <x-btn variant="primary" type="submit">Save terms</x-btn>
            </div>
        </x-card>
    </form>
</div>
