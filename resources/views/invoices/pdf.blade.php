@php
    $tenant = $document->tenant;
    $einvoice = $document->einvoice;
    $isGst = $document->isGst();
    $hsn = $isGst ? $document->hsnSummary() : [];
    $brand = $tenant->branding ?? [];
    $bank = $tenant->setting('bank', []);
    $logoPath = !empty($brand['logo']) ? storage_path('app/public/'.$brand['logo']) : null;
    $signPath = !empty($brand['signature']) ? storage_path('app/public/'.$brand['signature']) : null;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #111; margin: 0; }
        .wrap { border: 1px solid #333; padding: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; }
        .no-border, .no-border td { border: none; padding: 2px 0; }
        .right { text-align: right; } .center { text-align: center; }
        h1 { font-size: 16px; margin: 0 0 6px; text-align: center; letter-spacing: 1px; }
        .muted { color: #555; font-size: 10px; }
        .totals td { border: none; padding: 2px 4px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>{{ strtoupper($document->title) }}</h1>
    @if ($isGst && $document->reverse_charge)<div class="center muted">(Tax payable under reverse charge)</div>@endif

    <table class="no-border">
        <tr>
            <td style="width:60%">
                @if ($logoPath && file_exists($logoPath))
                    <img src="{{ $logoPath }}" style="max-height:46px; max-width:170px; margin-bottom:4px"><br>
                @endif
                <strong style="font-size:14px">{{ $tenant->legal_name ?: $tenant->name }}</strong><br>
                <span class="muted">
                    {{ $tenant->setting('address.line1') }}, {{ $tenant->setting('address.city') }} - {{ $tenant->setting('address.pincode') }}<br>
                    @if ($isGst)
                        GSTIN: <strong>{{ $tenant->gstin }}</strong> &nbsp; State: {{ \App\Support\Gstin::stateName($tenant->state_code) }} ({{ $tenant->state_code }})<br>
                    @endif
                    @if ($tenant->pan) PAN: {{ $tenant->pan }}<br> @endif
                    @if ($tenant->setting('contact.phone')) Ph: {{ $tenant->setting('contact.phone') }} @endif
                    @if ($tenant->setting('contact.email')) · {{ $tenant->setting('contact.email') }} @endif
                </span>
            </td>
            <td class="right">
                <strong>{{ $document->number }}</strong><br>
                <span class="muted">
                    Date: {{ $document->issue_date->format('d-m-Y') }}<br>
                    @if ($document->due_date) Due: {{ $document->due_date->format('d-m-Y') }}<br> @endif
                    @if ($isGst)
                        Place of Supply: {{ \App\Support\Gstin::stateName($document->place_of_supply_code) }} ({{ $document->place_of_supply_code }})
                    @endif
                </span>
            </td>
        </tr>
    </table>

    <table style="margin-top:8px">
        <tr>
            <td style="width:50%">
                <strong>Bill To</strong><br>
                {{ $document->party?->name }}<br>
                <span class="muted">
                    {{ $document->party?->billing_address }}<br>
                    {{ $document->party?->billing_city }} {{ $document->party?->billing_pincode }}<br>
                    @if ($isGst) GSTIN: {{ $document->party?->gstin ?: 'Unregistered' }} @endif
                </span>
            </td>
            <td>
                <strong>Ship To</strong><br>
                <span class="muted">{{ data_get($document->party?->shipping_address, 'line1', $document->party?->billing_address) }}</span>
            </td>
        </tr>
    </table>

    <table style="margin-top:8px">
        <thead>
        <tr>
            <th style="width:4%">#</th>
            <th>Description</th>
            @if ($isGst)<th style="width:8%">HSN/SAC</th>@endif
            <th style="width:8%">Qty</th>
            <th style="width:10%">Rate</th>
            <th style="width:8%">Disc</th>
            @if ($isGst)
                <th style="width:11%">Taxable</th>
                @if ($document->is_interstate)
                    <th style="width:13%">IGST</th>
                @else
                    <th style="width:11%">CGST</th>
                    <th style="width:11%">SGST</th>
                @endif
            @endif
            <th style="width:12%">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($document->lines as $i => $line)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $line->name }}<br><span class="muted">{{ $line->description }}</span></td>
                @if ($isGst)<td class="center">{{ $line->hsn_sac }}</td>@endif
                <td class="right">{{ rtrim(rtrim(number_format($line->quantity, 3), '0'), '.') }} {{ $line->uqc }}</td>
                <td class="right">{{ number_format($line->rate, 2) }}</td>
                <td class="right">{{ number_format($line->discount_amount, 2) }}</td>
                @if ($isGst)
                    <td class="right">{{ number_format($line->taxable_value, 2) }}</td>
                    @if ($document->is_interstate)
                        <td class="right">{{ number_format($line->igst_amount, 2) }} <span class="muted">({{ (float) $line->gst_rate }}%)</span></td>
                    @else
                        <td class="right">{{ number_format($line->cgst_amount, 2) }} <span class="muted">({{ (float) $line->gst_rate / 2 }}%)</span></td>
                        <td class="right">{{ number_format($line->sgst_amount, 2) }} <span class="muted">({{ (float) $line->gst_rate / 2 }}%)</span></td>
                    @endif
                @endif
                <td class="right">{{ number_format($line->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="no-border" style="margin-top:8px">
        <tr>
            <td style="width:58%; vertical-align:top">
                @if ($isGst)
                <strong>HSN Summary</strong>
                <table style="margin-top:4px">
                    <tr><th>HSN</th><th>Taxable</th><th>CGST</th><th>SGST</th><th>IGST</th></tr>
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
                <p class="muted" style="margin-top:6px"><strong>Amount in words:</strong> {{ $document->amount_in_words }}</p>
                @if ($einvoice?->irn)
                    <p class="muted"><strong>IRN:</strong> {{ $einvoice->irn }}<br>
                       <strong>Ack No:</strong> {{ $einvoice->ack_no }} &nbsp; <strong>Ack Date:</strong> {{ $einvoice->ack_date?->format('d-m-Y H:i') }}</p>
                    {{-- SVG backend keeps the QR pure-PHP (no imagick/GD dependency at render time). --}}
                    <img src="data:image/svg+xml;base64,{{ base64_encode(QrCode::format('svg')->size(110)->margin(0)->errorCorrection('M')->generate($einvoice->signed_qr_code)) }}" alt="e-Invoice QR" width="110" height="110">
                @endif
            </td>
            <td style="vertical-align:top">
                <table class="totals">
                    <tr><td>Subtotal</td><td class="right">{{ number_format($document->subtotal, 2) }}</td></tr>
                    @if ($document->discount_total > 0)
                        <tr><td>Discount</td><td class="right">-{{ number_format($document->discount_total, 2) }}</td></tr>
                    @endif
                    @if ($isGst)
                    <tr><td>Taxable Value</td><td class="right">{{ number_format($document->taxable_value, 2) }}</td></tr>
                    @if ($document->is_interstate)
                        <tr><td>IGST</td><td class="right">{{ number_format($document->igst_total, 2) }}</td></tr>
                    @else
                        <tr><td>CGST</td><td class="right">{{ number_format($document->cgst_total, 2) }}</td></tr>
                        <tr><td>SGST/UTGST</td><td class="right">{{ number_format($document->sgst_total, 2) }}</td></tr>
                    @endif
                    @endif
                    @if ($document->cess_total > 0)<tr><td>Cess</td><td class="right">{{ number_format($document->cess_total, 2) }}</td></tr>@endif
                    @if ($document->shipping_charge > 0)<tr><td>Shipping</td><td class="right">{{ number_format($document->shipping_charge, 2) }}</td></tr>@endif
                    @if ($document->tcs_total > 0)<tr><td>TCS</td><td class="right">{{ number_format($document->tcs_total, 2) }}</td></tr>@endif
                    @if ($document->tds_total > 0)<tr><td>TDS</td><td class="right">-{{ number_format($document->tds_total, 2) }}</td></tr>@endif
                    @if (abs((float) $document->round_off) >= 0.005)
                        <tr><td>Round Off</td><td class="right">{{ number_format($document->round_off, 2) }}</td></tr>
                    @endif
                    <tr><td><strong>Grand Total</strong></td><td class="right"><strong>₹ {{ number_format($document->grand_total, 2) }}</strong></td></tr>
                    @if ($document->amount_paid > 0)
                        <tr><td>Paid</td><td class="right">{{ number_format($document->amount_paid, 2) }}</td></tr>
                    @endif
                    <tr><td><strong>Balance Due</strong></td><td class="right"><strong>₹ {{ number_format($document->balance_due, 2) }}</strong></td></tr>
                </table>
                <div style="margin-top:14px; text-align:center" class="muted">
                    For {{ $tenant->legal_name ?: $tenant->name }}<br>
                    @if ($signPath && file_exists($signPath))
                        <img src="{{ $signPath }}" style="max-height:44px; max-width:150px; margin:4px 0">
                    @else
                        <br><br><br>
                    @endif
                    <br>Authorised Signatory
                </div>
            </td>
        </tr>
    </table>

    @if (!empty(array_filter((array) $bank)))
        <p class="muted" style="margin-top:6px"><strong>Bank details:</strong>
            @if (!empty($bank['name'])) {{ $bank['name'] }} @endif
            @if (!empty($bank['account'])) · A/c {{ $bank['account'] }} @endif
            @if (!empty($bank['ifsc'])) · IFSC {{ $bank['ifsc'] }} @endif
            @if (!empty($bank['upi'])) · UPI {{ $bank['upi'] }} @endif
        </p>
    @endif

    @if ($document->terms)
        <p class="muted"><strong>Terms &amp; Conditions:</strong><br>{!! nl2br(e($document->terms)) !!}</p>
    @endif

    @if ($tenant->setting('invoice_footer'))
        <p class="center muted" style="margin-top:8px; border-top:1px solid #ddd; padding-top:6px">{{ $tenant->setting('invoice_footer') }}</p>
    @endif
</div>
</body>
</html>
