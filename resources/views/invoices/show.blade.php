@extends('layouts.app')
@section('title', $document->number)

@section('content')
    @php
        $whatsappConfigured = app(\App\Services\WhatsApp\WhatsAppService::class)->isConfigured();
    @endphp

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ $document->title }} {{ $document->number }}</h1>
            <p class="text-slate-500 text-sm">{{ $document->party?->name }} · {{ $document->issue_date->format('d M Y') }}</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            {{-- WhatsApp Share (opens wa.me link on user's phone) --}}
            <a class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 font-medium text-white hover:bg-emerald-700"
               href="{{ $share['whatsapp'] }}" target="_blank" rel="noopener">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.7 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.3-.5 0-.2 0-.4-.1-.5 0-.2-.7-1.5-.9-2.1-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.4 1.9.8 2.6.9 3.5.7.6-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.5-.3M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z"/></svg>
                WhatsApp
            </a>

            {{-- WhatsApp Business API Send --}}
            @if ($whatsappConfigured)
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 font-medium text-emerald-700 hover:bg-emerald-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                        Send via API
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="m19 9-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak x-transition.origin.top.right
                         class="absolute right-0 z-30 mt-2 w-80 rounded-xl border border-slate-200 bg-white p-4 shadow-lg">
                        <form method="POST" action="{{ route('documents.whatsapp', $document) }}">
                            @csrf
                            <p class="mb-3 text-sm font-medium text-slate-800">Send via WhatsApp Business API</p>

                            <div class="mb-3">
                                <label class="mb-1 block text-xs font-medium text-slate-600">Recipient phone</label>
                                <input type="text" name="phone"
                                       value="{{ $document->party?->phone }}"
                                       placeholder="+91 98765 43210"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                            </div>

                            <div class="mb-3">
                                <label class="mb-1 block text-xs font-medium text-slate-600">Custom message (optional)</label>
                                <textarea name="message" rows="3"
                                          placeholder="Leave blank for default message..."
                                          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm"></textarea>
                            </div>

                            <p class="mb-3 text-xs text-slate-500">
                                📎 The invoice PDF will be attached automatically.
                            </p>

                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                    Send Now
                                </button>

                                @if ($document->balance_due > 0)
                                    <button type="submit" form="reminder-form" formaction="{{ route('documents.reminder', $document) }}"
                                            class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700 hover:bg-amber-100">
                                        Reminder
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Hidden reminder form --}}
                <form id="reminder-form" method="POST" action="{{ route('documents.reminder', $document) }}" class="hidden">@csrf</form>
            @else
                <a href="{{ route('settings.index', ['tab' => 'whatsapp']) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-500 hover:bg-slate-100"
                   title="Set up WhatsApp Business API to send invoices automatically">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
                    Setup WhatsApp API
                </a>
            @endif

            @if (! $document->einvoice?->isGenerated() && $document->status !== 'cancelled')
                <a class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 font-medium text-amber-700 hover:bg-amber-100"
                   href="{{ route('documents.edit', $document) }}">Edit</a>
            @endif
            <a class="rounded-lg border border-slate-300 bg-white px-3 py-2 hover:bg-slate-50" href="{{ $share['email'] }}">Email</a>
            <a class="rounded-lg border border-slate-300 bg-white px-3 py-2 hover:bg-slate-50" href="{{ route('documents.pdf', $document) }}" target="_blank">PDF</a>
            <a class="rounded-lg border border-slate-300 bg-white px-3 py-2 hover:bg-slate-50" href="{{ route('documents.pdf', [$document, 'download' => 1]) }}">Download</a>
            <button type="button" onclick="navigator.clipboard.writeText('{{ $share['public'] }}');this.textContent='Link copied'"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 hover:bg-slate-50">Copy link</button>

            @if ($document->doc_type === 'quotation')
                <form method="post" action="{{ route('documents.convert', [$document, 'invoice']) }}">@csrf
                    <button class="rounded bg-indigo-600 px-3 py-2 text-white">Convert to Invoice</button>
                </form>
            @endif

            @if (in_array($document->doc_type, ['invoice', 'bill_of_supply'], true) && ! $document->recurring_invoice_id)
                <a class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 font-medium text-indigo-700 hover:bg-indigo-100"
                   href="{{ route('recurring.create', ['from' => $document->id]) }}">Repeat monthly… Make recurring</a>
            @endif

            @if ($document->recurring_invoice_id)
                <a class="rounded-lg border border-slate-300 bg-white px-3 py-2 hover:bg-slate-50"
                   href="{{ route('recurring.show', $document->recurring_invoice_id) }}">From recurring schedule</a>
            @endif

            @if ($document->status === 'draft')
                <form method="post" action="{{ route('documents.destroy', $document) }}"
                      onsubmit="return confirm('Delete this draft? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-rose-600 hover:bg-rose-50">Delete draft</button>
                </form>
            @endif

            @if ($document->isEinvoiceApplicable() && ! $document->einvoice?->isGenerated())
                <form method="post" action="{{ route('einvoice.generate', $document) }}">@csrf
                    <button class="rounded bg-emerald-600 px-3 py-2 text-white">Generate IRN</button>
                </form>
            @endif
        </div>
    </div>

    @if ($document->grand_total >= config('gst.ewaybill_threshold') && ! $document->ewayBill)
        <form method="post" action="{{ route('ewaybill.generate', $document) }}" class="mb-4 rounded-xl bg-white border p-4 flex flex-wrap gap-2 items-end text-sm">
            @csrf
            <div><label class="block text-xs text-slate-500">Mode</label>
                <select name="transport_mode" class="rounded border-slate-300">
                    @foreach (config('gst.transport_modes') as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select></div>
            <div><label class="block text-xs text-slate-500">Vehicle No</label><input name="vehicle_no" class="rounded border-slate-300"></div>
            <div><label class="block text-xs text-slate-500">Distance (km)</label><input name="distance_km" type="number" value="0" class="rounded border-slate-300 w-28"></div>
            <button class="rounded bg-slate-800 px-4 py-2 text-white">Generate e-Way Bill</button>
        </form>
    @endif

    <div class="rounded-xl bg-white border p-4">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500"><tr>
                <th class="py-2">Item</th><th>HSN</th><th class="text-right">Qty</th><th class="text-right">Rate</th>
                <th class="text-right">Taxable</th><th class="text-right">GST</th><th class="text-right">Total</th>
            </tr></thead>
            <tbody>
            @foreach ($document->lines as $line)
                <tr class="border-t">
                    <td class="py-2">{{ $line->name }}</td>
                    <td>{{ $line->hsn_sac }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format($line->quantity, 3), '0'), '.') }}</td>
                    <td class="text-right">{{ number_format($line->rate, 2) }}</td>
                    <td class="text-right">{{ number_format($line->taxable_value, 2) }}</td>
                    <td class="text-right">{{ (float) $line->gst_rate }}% ({{ number_format($line->cgst_amount + $line->sgst_amount + $line->igst_amount, 2) }})</td>
                    <td class="text-right">{{ number_format($line->line_total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="mt-4 text-right text-sm space-y-1">
            <div>Taxable: ₹ {{ number_format($document->taxable_value, 2) }}</div>
            <div>Tax: ₹ {{ number_format($document->tax_total, 2) }}</div>
            <div class="text-lg font-semibold">Total: ₹ {{ number_format($document->grand_total, 2) }}</div>
            <div>Balance due: ₹ {{ number_format($document->balance_due, 2) }}</div>
        </div>
    </div>

    @if ($share['upi'] && $document->balance_due > 0)
        <div class="mt-4 flex flex-wrap items-center gap-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <img src="data:image/svg+xml;base64,{{ base64_encode(QrCode::format('svg')->size(120)->margin(0)->generate($share['upi'])) }}"
                 alt="UPI QR" width="120" height="120" class="rounded-lg bg-white p-1.5">
            <div>
                <p class="font-medium text-emerald-900">Collect ₹{{ number_format($document->balance_due, 2) }} by UPI</p>
                <p class="mt-0.5 text-sm text-emerald-700">Show this QR to your customer, or share the invoice link.</p>
            </div>
        </div>
    @endif

    @if ($document->einvoice?->irn)
        <div class="mt-4 rounded-xl bg-white border p-4 text-sm">
            <div class="font-semibold mb-1">e-Invoice</div>
            <div class="font-mono break-all">IRN: {{ $document->einvoice->irn }}</div>
            <div>Ack: {{ $document->einvoice->ack_no }} · {{ $document->einvoice->ack_date?->format('d M Y H:i') }}</div>
        </div>
    @endif

    <form method="post" action="{{ route('documents.payment', $document) }}" class="mt-4 rounded-xl bg-white border p-4 flex gap-2 items-end text-sm">
        @csrf
        <div><label class="block text-xs text-slate-500">Amount</label><input name="amount" type="number" step="0.01" value="{{ $document->balance_due }}" class="rounded border-slate-300"></div>
        <div><label class="block text-xs text-slate-500">Date</label><input name="paid_on" type="date" value="{{ now()->toDateString() }}" class="rounded border-slate-300"></div>
        <div><label class="block text-xs text-slate-500">Method</label>
            <select name="method" class="rounded border-slate-300">
                @foreach (['cash','bank','upi','card','cheque'] as $m)<option>{{ $m }}</option>@endforeach
            </select></div>
        <button class="rounded bg-emerald-600 px-4 py-2 text-white">Record payment</button>
    </form>

    {{-- Change history: every create/edit/payment is logged via laravel-activitylog. --}}
    <div class="mt-4 rounded-xl bg-white border p-4">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="font-semibold text-sm">Change history</h2>
            <span class="text-xs text-slate-400">Last {{ count($activities) }} entries</span>
        </div>

        <ol class="relative space-y-3 border-l border-slate-200 pl-4 text-sm">
            @forelse ($activities as $activity)
                <li class="relative">
                    <span class="absolute -left-[21px] top-1 h-2.5 w-2.5 rounded-full {{ $activity->event === 'updated' ? 'bg-amber-400' : ($activity->event === 'deleted' ? 'bg-rose-400' : 'bg-emerald-400') }}"></span>
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="text-slate-700">
                            {{ $activity->description }}
                            <span class="text-slate-400">by {{ $activity->causer?->name ?? 'Scheduler / system' }}</span>
                        </p>
                        <p class="text-xs text-slate-400">{{ $activity->created_at->format('d M Y, h:i A') }}</p>
                    </div>

                    @if ($activity->event === 'updated' && ! empty($activity->changes['attributes']))
                        <ul class="mt-1 space-y-0.5 text-xs text-slate-500">
                            @foreach ($activity->changes['attributes'] as $field => $new)
                                <li>
                                    <span class="font-medium text-slate-600">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                    <span class="line-through decoration-rose-300">{{ $activity->changes['old'][$field] ?? '—' }}</span>
                                    → <span class="text-slate-700">{{ is_scalar($new) ? $new : json_encode($new) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @empty
                <li class="text-slate-400">No changes recorded yet.</li>
            @endforelse
        </ol>
    </div>
@endsection
