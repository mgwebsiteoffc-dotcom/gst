@php
    $tenant = $document->tenant;
    $einvoice = $document->einvoice;
    $isGst = $document->isGst();
    $hsn = $isGst ? $document->hsnSummary() : [];
    $brand = $tenant->branding ?? [];
    $bank = $tenant->setting('bank', []);
    $accent = $brand['accent_color'] ?? '#4f46e5';
    $logoPath = !empty($brand['logo']) ? storage_path('app/public/'.$brand['logo']) : null;
    $signPath = !empty($brand['signature']) ? storage_path('app/public/'.$brand['signature']) : null;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* Compact template: denser type and tighter spacing for line-heavy invoices. */
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        body { font-size: 8.5px; color: #1e293b; margin: 0; padding: 14px 18px; }
        table { width: 100%; border-collapse: collapse; }
        .right { text-align: right; } .center { text-align: center; }
        .muted { color: #64748b; font-size: 7.5px; }

        .head td { vertical-align: top; padding-bottom: 6px; }
        .doc-title { font-size: 13px; font-weight: bold; letter-spacing: 1.5px; color: {{ $accent }}; text-transform: uppercase; }
        .company { font-size: 11px; font-weight: bold; }
        .rule { border-bottom: 1.5px solid {{ $accent }}; margin: 2px 0 8px; }

        .cells td { border: 1px solid #e2e8f0; padding: 4px 6px; vertical-align: top; }
        .cells .label { font-size: 7px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: {{ $accent }}; }

        .items th { background: #f1f5f9; font-size: 7px; text-transform: uppercase; letter-spacing: .4px; color: #475569; padding: 3px 5px; text-align: left; border-bottom: 1px solid #cbd5e1; }
        .items td { padding: 3px 5px; border-bottom: 1px solid #eef2f7; }
        .items td.name { font-weight: 600; }
        .items td.name .desc { display: block; font-weight: normal; color: #94a3b8; font-size: 7px; }

        .totals td { padding: 1.5px 4px; }
        .totals .grand td { font-size: 10px; font-weight: bold; border-top: 1.5px solid {{ $accent }}; padding-top: 4px; }
        .totals .balance td { font-weight: bold; color: #dc2626; }

        .hsn th, .hsn td { border: 1px solid #e2e8f0; padding: 2.5px 4px; font-size: 7.5px; }
        .hsn th { background: #f8fafc; text-transform: uppercase; font-size: 6.8px; color: #64748b; }

        .foot { margin-top: 8px; }
        .sign { text-align: center; color: #64748b; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td style="width:58%">
                @if ($logoPath && file_exists($logoPath))
                    <img src="{{ $logoPath }}" style="max-height:34px; max-width:140px; margin-bottom:2px"><br>
                @endif
                <span class="company">{{ $tenant->legal_name ?: $tenant->name }}</span><br>
                <span class="muted">
                    {{ $tenant->setting('address.line1') }}, {{ $tenant->setting('address.city') }} - {{ $tenant->setting('address.pincode') }}
                    @if ($isGst) · GSTIN: <strong>{{ $tenant->gstin }}</strong>@endif
                    @if ($tenant->setting('contact.phone')) · Ph: {{ $tenant->setting('contact.phone') }}@endif
                </span>
            </td>
            <td class="right">
                <div class="doc-title">{{ $document->title }}</div>
                <strong>{{ $document->number }}</strong><br>
                <span class="muted">
                    Date: {{ $document->issue_date->format('d-m-Y') }}
                    @if ($document->due_date) · Due: {{ $document->due_date->format('d-m-Y') }}@endif
                    @if ($isGst)<br>Place of Supply: {{ \App\Support\Gstin::stateName($document->place_of_supply_code) }} ({{ $document->place_of_supply_code }})@endif
                    @if ($isGst && $document->reverse_charge)<br>(Tax payable under reverse charge)@endif
                </span>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <table class="cells" style="margin-bottom:8px">
        <tr>
            <td style="width:60%">
                <div class="label">Bill To</div>
                <strong>{{ $document->party?->name }}</strong>
                <span class="muted"><br>
                    {{ $document->party?->billing_address }}
                    {{ $document->party?->billing_city }} {{ $document->party?->billing_pincode }}
                    @if ($isGst)<br>GSTIN: {{ $document->party?->gstin ?: 'Unregistered' }}@endif
                    @if ($document->party?->phone)<br>Ph: {{ $document->party?->phone }}@endif
                </span>
            </td>
            <td style="width:40%">
                @if ($tenant->setting('show_ship_to', false))
                    <div class="label">Ship To</div>
                    <span class="muted">{{ data_get($document->party?->shipping_address, 'line1', $document->party?->billing_address) }}</span>
                @else
                    <div class="label">Details</div>
                    <span class="muted">
                        {{ \App\Models\Document::TYPES[$document->doc_type] ?? 'Document' }}
                        @if ($isGst)<br>Supply: {{ $document->supply_type }}@endif
                    </span>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
        <tr>
            <th style="width:3%">#</th>
            <th>Description</th>
            @if ($isGst)<th style="width:8%">HSN</th>@endif
            <th class="right" style="width:9%">Qty</th>
            <th class="right" style="width:10%">Rate</th>
            <th class="right" style="width:7%">Disc</th>
            @if ($isGst)
                <th class="right" style="width:10%">Taxable</th>
                @if ($document->is_interstate)
                    <th class="right" style="width:11%">IGST</th>
                @else
                    <th class="right" style="width:10%">CGST</th>
                    <th class="right" style="width:10%">SGST</th>
                @endif
            @endif
            <th class="right" style="width:11%">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($document->lines as $i => $line)
            <tr>
                <td class="center muted">{{ $i + 1 }}</td>
                <td class="name">{{ $line->name }}@if ($line->description)<span class="desc">{{ $line->description }}</span>@endif</td>
                @if ($isGst)<td>{{ $line->hsn_sac }}</td>@endif
                <td class="right">{{ rtrim(rtrim(number_format($line->quantity, 3), '0'), '.') }}</td>
                <td class="right">{{ number_format($line->rate, 2) }}</td>
                <td class="right">{{ $line->discount_amount > 0 ? number_format($line->discount_amount, 2) : '—' }}</td>
                @if ($isGst)
                    <td class="right">{{ number_format($line->taxable_value, 2) }}</td>
                    @if ($document->is_interstate)
                        <td class="right">{{ number_format($line->igst_amount, 2) }}</td>
                    @else
                        <td class="right">{{ number_format($line->cgst_amount, 2) }}</td>
                        <td class="right">{{ number_format($line->sgst_amount, 2) }}</td>
                    @endif
                @endif
                <td class="right">{{ number_format($line->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table style="margin-top:6px">
        <tr>
            <td style="width:58%; vertical-align:top; padding-right:10px">
                @if ($isGst && count($hsn))
                    <table class="hsn">
                        <tr><th>HSN</th><th class="right">Taxable</th><th class="right">CGST</th><th class="right">SGST</th><th class="right">IGST</th></tr>
                        @foreach ($hsn as $row)
                            <tr>
                                <td>{{ $row['hsn'] }}</td>
                                <td class="right">{{ number_format($row['taxable_value'], 2) }}</td>
                                <td class="right">{{ number_format($row['cgst'], 2) }}</td>
                                <td class="right">{{ number_format($row['sgst'], 2) }}</td>
                                <td class="right">{{ number_format($row['igst'], 2) }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
                <p class="muted" style="margin-top:5px"><strong>Amount in words:</strong> {{ $document->amount_in_words }}</p>
                @if ($einvoice?->irn)
                    <p class="muted"><strong>IRN:</strong> {{ $einvoice->irn }}<br>
                       <strong>Ack No:</strong> {{ $einvoice->ack_no }} · {{ $einvoice->ack_date?->format('d-m-Y H:i') }}</p>
                    <img src="data:image/svg+xml;base64,{{ base64_encode(QrCode::format('svg')->size(80)->margin(0)->errorCorrection('M')->generate($einvoice->signed_qr_code)) }}" alt="e-Invoice QR" width="80" height="80">
                @endif
                @if (!empty(array_filter((array) $bank)))
                    <p class="muted"><strong>Bank:</strong>
                        @if (!empty($bank['name'])) {{ $bank['name'] }}@endif
                        @if (!empty($bank['account'])) · A/c {{ $bank['account'] }}@endif
                        @if (!empty($bank['ifsc'])) · IFSC {{ $bank['ifsc'] }}@endif
                        @if (!empty($bank['upi'])) · UPI {{ $bank['upi'] }}@endif
                    </p>
                @endif
                @if ($document->notes)
                    <p class="muted"><strong>Notes:</strong> {!! nl2br(e($document->notes)) !!}</p>
                @endif
            </td>
            <td style="vertical-align:top">
                <table class="totals">
                    <tr><td>Subtotal</td><td class="right">{{ number_format($document->subtotal, 2) }}</td></tr>
                    @if ($document->discount_total > 0)
                        <tr><td>Discount</td><td class="right">-{{ number_format($document->discount_total, 2) }}</td></tr>
                    @endif
                    @if ($isGst)
                        <tr><td>Taxable</td><td class="right">{{ number_format($document->taxable_value, 2) }}</td></tr>
                        @if ($document->is_interstate)
                            <tr><td>IGST</td><td class="right">{{ number_format($document->igst_total, 2) }}</td></tr>
                        @else
                            <tr><td>CGST</td><td class="right">{{ number_format($document->cgst_total, 2) }}</td></tr>
                            <tr><td>SGST/UTGST</td><td class="right">{{ number_format($document->sgst_total, 2) }}</td></tr>
                        @endif
                        @if ($document->cess_total > 0)<tr><td>Cess</td><td class="right">{{ number_format($document->cess_total, 2) }}</td></tr>@endif
                    @endif
                    @if ($document->shipping_charge > 0)<tr><td>Shipping</td><td class="right">{{ number_format($document->shipping_charge, 2) }}</td></tr>@endif
                    @if ($document->tcs_total > 0)<tr><td>TCS</td><td class="right">{{ number_format($document->tcs_total, 2) }}</td></tr>@endif
                    @if ($document->tds_total > 0)<tr><td>TDS</td><td class="right">-{{ number_format($document->tds_total, 2) }}</td></tr>@endif
                    @if (abs((float) $document->round_off) >= 0.005)
                        <tr><td>Round Off</td><td class="right">{{ number_format($document->round_off, 2) }}</td></tr>
                    @endif
                    <tr class="grand"><td>Total</td><td class="right">₹ {{ number_format($document->grand_total, 2) }}</td></tr>
                    @if ($document->amount_paid > 0)
                        <tr><td>Paid</td><td class="right">{{ number_format($document->amount_paid, 2) }}</td></tr>
                    @endif
                    <tr class="balance"><td>Balance Due</td><td class="right">₹ {{ number_format($document->balance_due, 2) }}</td></tr>
                </table>
                <div class="sign foot">
                    @if ($signPath && file_exists($signPath))
                        <img src="{{ $signPath }}" style="max-height:34px; max-width:120px; margin-bottom:2px"><br>
                    @else
                        <br><br>
                    @endif
                    For {{ $tenant->legal_name ?: $tenant->name }}<br>Authorised Signatory
                </div>
            </td>
        </tr>
    </table>

    @if ($document->terms)
        <p class="muted" style="margin-top:6px"><strong>Terms:</strong> {!! nl2br(e($document->terms)) !!}</p>
    @endif

    @if ($tenant->setting('invoice_footer'))
        <p class="center muted" style="border-top:1px solid #e2e8f0; padding-top:4px; margin-top:6px">{{ $tenant->setting('invoice_footer') }}</p>
    @endif
</body>
</html>
