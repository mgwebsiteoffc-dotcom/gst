<!DOCTYPE html>
<html lang="en-IN" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $document->title }} {{ $document->number }} — {{ $document->tenant->name }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full py-6 text-slate-800 antialiased">
<div class="mx-auto max-w-2xl px-4">

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-lg font-semibold">{{ $document->tenant->legal_name ?: $document->tenant->name }}</p>
            <p class="text-sm text-slate-500">{{ $document->title }} {{ $document->number }}</p>
        </div>
        <a href="{{ route('documents.pdf', [$document, 'download' => 1]) }}"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Download PDF</a>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5">
            <div class="flex flex-wrap justify-between gap-4 text-sm">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Billed to</p>
                    <p class="mt-1 font-medium">{{ $document->party?->name }}</p>
                    <p class="text-slate-500">{{ $document->party?->billing_city }}</p>
                    @if ($document->party?->gstin)
                        <p class="font-mono text-xs text-slate-500">{{ $document->party->gstin }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Date</p>
                    <p class="mt-1">{{ $document->issue_date->format('d M Y') }}</p>
                    @if ($document->due_date)
                        <p class="text-xs text-slate-500">Due {{ $document->due_date->format('d M Y') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <table class="w-full min-w-[480px] text-sm">
            <thead class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-2.5 font-medium">Item</th>
                    <th class="px-3 text-right font-medium">Qty</th>
                    <th class="px-3 text-right font-medium">Rate</th>
                    <th class="px-5 text-right font-medium">Amount</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($document->lines as $line)
                <tr class="border-b border-slate-50">
                    <td class="px-5 py-3">
                        <div class="font-medium">{{ $line->name }}</div>
                        @if ($document->isGst() && $line->hsn_sac)
                            <div class="text-xs text-slate-400">HSN {{ $line->hsn_sac }} · GST {{ (float) $line->gst_rate }}%</div>
                        @endif
                    </td>
                    <td class="px-3 text-right tabular-nums">{{ rtrim(rtrim(number_format($line->quantity, 3), '0'), '.') }}</td>
                    <td class="px-3 text-right tabular-nums">{{ number_format($line->rate, 2) }}</td>
                    <td class="px-5 text-right tabular-nums">{{ number_format($line->line_total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="border-t border-slate-100 p-5">
            <dl class="ml-auto max-w-xs space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Taxable</dt><dd>₹{{ number_format($document->taxable_value, 2) }}</dd></div>
                @if ($document->isGst())
                    @if ($document->is_interstate)
                        <div class="flex justify-between"><dt class="text-slate-500">IGST</dt><dd>₹{{ number_format($document->igst_total, 2) }}</dd></div>
                    @else
                        <div class="flex justify-between"><dt class="text-slate-500">CGST</dt><dd>₹{{ number_format($document->cgst_total, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">SGST</dt><dd>₹{{ number_format($document->sgst_total, 2) }}</dd></div>
                    @endif
                @endif
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold">
                    <dt>Total</dt><dd>₹{{ number_format($document->grand_total, 2) }}</dd>
                </div>
                @if ($document->balance_due > 0)
                    <div class="flex justify-between text-rose-600"><dt>Balance due</dt><dd>₹{{ number_format($document->balance_due, 2) }}</dd></div>
                @else
                    <div class="flex justify-between text-emerald-600"><dt>Paid in full</dt><dd>✓</dd></div>
                @endif
            </dl>
        </div>

        @if ($upi && $document->balance_due > 0)
            <div class="border-t border-slate-100 bg-slate-50 p-5 text-center">
                <p class="text-sm font-medium">Pay by UPI</p>
                <p class="mb-3 text-xs text-slate-500">Scan with any UPI app, or tap on mobile</p>
                <img src="data:image/svg+xml;base64,{{ base64_encode(QrCode::format('svg')->size(160)->margin(0)->generate($upi)) }}"
                     alt="UPI QR code" width="160" height="160" class="mx-auto">
                <a href="{{ $upi }}" class="mt-3 inline-block rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white">
                    Pay ₹{{ number_format($document->balance_due, 2) }}
                </a>
            </div>
        @endif
    </div>

    <p class="mt-4 text-center text-xs text-slate-400">
        Powered by {{ config('app.name') }}
    </p>
</div>
</body>
</html>
