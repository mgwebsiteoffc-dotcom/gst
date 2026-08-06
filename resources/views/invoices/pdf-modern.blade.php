@php
    $tenant = $document->tenant;
    $einvoice = $document->einvoice;
    $isGst = $document->isGst();
    $hsn = $isGst ? $document->hsnSummary() : [];
    $gstRates = $document->lines->pluck('gst_rate')->unique();
    $uniformGstRate = $gstRates->count() === 1 ? (float) $gstRates->first() : null;
    $fmtRate = fn ($r) => rtrim(rtrim(number_format($r, 2), '0'), '.');
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
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        body { font-size: 10px; color: #1e293b; margin: 0; padding: 0; }
        .page { padding: 0; }

        /* Header */
        .header { background: {{ $accent }}; color: #fff; padding: 20px 24px; display: table; width: 100%; }
        .header-cell { display: table-cell; vertical-align: middle; }
        .header-left { width: 60%; }
        .header-right { width: 40%; text-align: right; }
        .company-name { font-size: 18px; font-weight: bold; margin-bottom: 2px; }
        .company-details { font-size: 9px; opacity: 0.85; line-height: 1.5; }
        .doc-title { font-size: 22px; font-weight: bold; letter-spacing: 2px; margin-bottom: 4px; }
        .doc-meta { font-size: 9px; opacity: 0.85; line-height: 1.6; }

        /* Body */
        .body { padding: 16px 24px; }

        /* Parties */
        .parties { display: table; width: 100%; margin-bottom: 16px; }
        .party-cell { display: table-cell; width: 50%; vertical-align: top; padding: 12px; background: #f8fafc; border-radius: 6px; }
        .party-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: {{ $accent }}; margin-bottom: 6px; }
        .party-name { font-size: 12px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
        .party-details { font-size: 9px; color: #64748b; line-height: 1.5; }

        /* Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .items-table thead th {
            background: {{ $accent }};
            color: #fff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 6px;
            text-align: left;
        }
        .items-table thead th:first-child { border-radius: 4px 0 0 0; }
        .items-table thead th:last-child { border-radius: 0 4px 0 0; }
        .items-table tbody td {
            padding: 7px 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9.5px;
        }
        .items-table tbody tr:nth-child(even) { background: #f8fafc; }
        .items-table .right { text-align: right; }
        .items-table .center { text-align: center; }
        .items-table .item-name { font-weight: 600; color: #0f172a; }
        .items-table .item-desc { font-size: 8px; color: #94a3b8; margin-top: 1px; }

        /* Summary */
        .summary { display: table; width: 100%; margin-top: 8px; }
        .summary-left { display: table-cell; width: 55%; vertical-align: top; padding-right: 16px; }
        .summary-right { display: table-cell; width: 45%; vertical-align: top; }

        /* Totals */
        .totals-table { width: 100%; }
        .totals-table td { padding: 4px 8px; font-size: 9.5px; border-bottom: 1px solid #f1f5f9; }
        .totals-table .right { text-align: right; }
        .totals-table .grand { background: {{ $accent }}; color: #fff; font-size: 12px; font-weight: bold; }
        .totals-table .grand td { padding: 10px 8px; border: none; border-radius: 0 0 6px 6px; }
        .totals-table .balance td { font-weight: bold; color: #dc2626; }

        /* HSN table */
        .hsn-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .hsn-table th { background: #f1f5f9; font-size: 8px; text-transform: uppercase; padding: 5px 4px; text-align: left; border: 1px solid #e2e8f0; }
        .hsn-table td { font-size: 9px; padding: 4px; border: 1px solid #e2e8f0; }
        .hsn-table .right { text-align: right; }

        /* Footer */
        .footer-section { margin-top: 16px; }
        .words { font-size: 9px; color: #475569; padding: 8px 12px; background: #f8fafc; border-radius: 4px; margin-bottom: 10px; }
        .words strong { color: #0f172a; }
        .bank-box { font-size: 9px; color: #64748b; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 4px; margin-bottom: 10px; }
        .bank-box strong { color: #0f172a; }
        .terms { font-size: 8px; color: #94a3b8; line-height: 1.5; margin-top: 8px; }
        .terms strong { color: #64748b; }

        /* Signature */
        .signature { text-align: center; margin-top: 20px; }
        .signature .company { font-size: 9px; color: #64748b; margin-bottom: 4px; }
        .signature .line { border-top: 1px solid #cbd5e1; width: 160px; margin: 0 auto; padding-top: 4px; font-size: 8px; color: #94a3b8; }

        .footer-bar { background: {{ $accent }}; color: #fff; text-align: center; padding: 8px; font-size: 8px; opacity: 0.8; margin-top: 16px; }

        .muted { color: #94a3b8; }
        .section-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: {{ $accent }}; margin-bottom: 6px; }
    </style>
</head>
<body>
<div class="page">
    {{-- Header --}}
    <div class="header">
        <div class="header-cell header-left">
            @if ($logoPath && file_exists($logoPath))
                <img src="{{ $logoPath }}" style="max-height:40px; max-width:160px; margin-bottom:6px; filter: brightness(0) invert(1);"><br>
            @endif
            <div class="company-name">{{ $tenant->legal_name ?: $tenant->name }}</div>
            <div class="company-details">
                {{ $tenant->setting('address.line1') }}@if($tenant->setting('address.city')), {{ $tenant->setting('address.city') }}@endif @if($tenant->setting('address.pincode'))- {{ $tenant->setting('address.pincode') }}@endif<br>
                @if ($isGst && $tenant->gstin) GSTIN: {{ $tenant->gstin }} &nbsp;|&nbsp; State: {{ \App\Support\Gstin::stateName($tenant->state_code) }} ({{ $tenant->state_code }})<br> @endif
                @if ($tenant->pan) PAN: {{ $tenant->pan }} @endif
                @if ($tenant->setting('contact.phone')) &nbsp;|&nbsp; Ph: {{ $tenant->setting('contact.phone') }} @endif
                @if ($tenant->setting('contact.email')) &nbsp;|&nbsp; {{ $tenant->setting('contact.email') }} @endif
            </div>
        </div>
        <div class="header-cell header-right">
            <div class="doc-title">{{ strtoupper($document->title) }}</div>
            <div class="doc-meta">
                <strong># {{ $document->number }}</strong><br>
                Date: {{ $document->issue_date->format('d M Y') }}<br>
                @if ($document->due_date) Due: {{ $document->due_date->format('d M Y') }}<br> @endif
                @if ($isGst) Place of Supply: {{ \App\Support\Gstin::stateName($document->place_of_supply_code) }} ({{ $document->place_of_supply_code }}) @endif
            </div>
        </div>
    </div>

    @if ($isGst && $document->reverse_charge)
        <div style="text-align:center; padding:4px; background:#fef3c7; font-size:9px; color:#92400e; font-weight:bold;">
            Tax payable under Reverse Charge Mechanism
        </div>
    @endif

    <div class="body">
        {{-- Parties --}}
        @php
            $showShipTo = $tenant->setting('show_ship_to', false);
        @endphp
        <div class="parties">
            <div class="party-cell" style="{{ $showShipTo ? 'padding-right:8px;' : '' }}">
                <div class="party-label">Bill To</div>
                <div class="party-name">{{ $document->party?->name ?: 'Walk-in Customer' }}</div>
                <div class="party-details">
                    {{ $document->party?->billing_address }}<br>
                    @if($document->party?->billing_city) {{ $document->party?->billing_city }} @endif
                    @if($document->party?->billing_state) , {{ $document->party?->billing_state }} @endif
                    @if($document->party?->billing_pincode) - {{ $document->party?->billing_pincode }} @endif<br>
                    @if ($isGst) GSTIN: {{ $document->party?->gstin ?: 'Unregistered' }} @endif
                    @if ($document->party?->phone) <br>Ph: {{ $document->party?->phone }} @endif
                    @if ($document->party?->email) <br>{{ $document->party?->email }} @endif
                </div>
            </div>
            @if ($showShipTo)
            <div class="party-cell" style="margin-left:8px;">
                <div class="party-label">Ship To</div>
                <div class="party-name">{{ data_get($document->party?->shipping_address, 'name', $document->party?->name) }}</div>
                <div class="party-details">
                    {{ data_get($document->party?->shipping_address, 'line1', $document->party?->billing_address) }}<br>
                    {{ data_get($document->party?->shipping_address, 'city', $document->party?->billing_city) }}
                    {{ data_get($document->party?->shipping_address, 'pincode', $document->party?->billing_pincode) }}
                </div>
            </div>
            @endif
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:4%; text-align:center">#</th>
                    <th style="width:28%">Item / Description</th>
                    @if ($isGst) <th style="width:8%">HSN/SAC</th> @endif
                    <th style="width:7%; text-align:center">Qty</th>
                    <th style="width:9%; text-align:right">Rate</th>
                    <th style="width:6%; text-align:right">Disc</th>
                    @if ($isGst)
                        <th style="width:10%; text-align:right">Taxable</th>
                        @if ($document->is_interstate)
                            <th style="width:12%; text-align:right">IGST @if ($uniformGstRate !== null)({{ $fmtRate($uniformGstRate) }}%)@endif</th>
                        @else
                            <th style="width:10%; text-align:right">CGST @if ($uniformGstRate !== null)({{ $fmtRate($uniformGstRate / 2) }}%)@endif</th>
                            <th style="width:10%; text-align:right">SGST @if ($uniformGstRate !== null)({{ $fmtRate($uniformGstRate / 2) }}%)@endif</th>
                        @endif
                    @endif
                    <th style="width:12%; text-align:right">Amount</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($document->lines as $i => $line)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $line->name }}</div>
                        @if ($line->description) <div class="item-desc">{{ $line->description }}</div> @endif
                    </td>
                    @if ($isGst) <td class="center">{{ $line->hsn_sac }}</td> @endif
                    <td class="center">{{ rtrim(rtrim(number_format($line->quantity, 3), '0'), '.') }} {{ $line->uqc }}</td>
                    <td class="right">{{ number_format($line->rate, 2) }}</td>
                    <td class="right">{{ $line->discount_amount > 0 ? number_format($line->discount_amount, 2) : '—' }}</td>
                    @if ($isGst)
                        <td class="right">{{ number_format($line->taxable_value, 2) }}</td>
                        @if ($document->is_interstate)
                            <td class="right">{{ number_format($line->igst_amount, 2) }}@if ($uniformGstRate === null) <span class="muted">({{ $fmtRate($line->gst_rate) }}%)</span>@endif</td>
                        @else
                            <td class="right">{{ number_format($line->cgst_amount, 2) }}@if ($uniformGstRate === null) <span class="muted">({{ $fmtRate($line->gst_rate / 2) }}%)</span>@endif</td>
                            <td class="right">{{ number_format($line->sgst_amount, 2) }}@if ($uniformGstRate === null) <span class="muted">({{ $fmtRate($line->gst_rate / 2) }}%)</span>@endif</td>
                        @endif
                    @endif
                    <td class="right" style="font-weight:600">{{ number_format($line->line_total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        {{-- Summary: Left (HSN + words) | Right (Totals + Sign) --}}
        <div class="summary">
            <div class="summary-left">
                @if ($isGst && !empty($hsn))
                    <div class="section-label">HSN Summary</div>
                    <table class="hsn-table">
                        <tr>
                            <th>HSN/SAC</th><th>Qty</th><th class="right">Taxable</th>
                            <th class="right">CGST</th><th class="right">SGST</th><th class="right">IGST</th>
                        </tr>
                        @foreach ($hsn as $row)
                            <tr>
                                <td>{{ $row['hsn'] }}</td>
                               <td>{{ rtrim(rtrim(number_format($row['quantity'], 2), '0'), '.') }}</td>
                                <td class="right">{{ number_format($row['taxable_value'], 2) }}</td>
                                <td class="right">{{ number_format($row['cgst'], 2) }}</td>
                                <td class="right">{{ number_format($row['sgst'], 2) }}</td>
                                <td class="right">{{ number_format($row['igst'], 2) }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif

                <div class="words" style="margin-top:10px">
                    <strong>Amount in Words:</strong> {{ $document->amount_in_words }}
                </div>

                @if (!empty(array_filter((array) $bank)))
                    <div class="bank-box">
                        <strong>Bank Details:</strong><br>
                        @if (!empty($bank['name'])) {{ $bank['name'] }}<br> @endif
                        @if (!empty($bank['account'])) A/c No: {{ $bank['account'] }}<br> @endif
                        @if (!empty($bank['ifsc'])) IFSC: {{ $bank['ifsc'] }}<br> @endif
                        @if (!empty($bank['branch'])) Branch: {{ $bank['branch'] }}<br> @endif
                        @if (!empty($bank['upi'])) UPI: {{ $bank['upi'] }} @endif
                    </div>
                @endif

                @if ($einvoice?->irn)
                    <div style="margin-top:8px">
                        <div class="section-label">e-Invoice</div>
                        <div style="font-size:8px; color:#64748b; word-break:break-all">
                            <strong>IRN:</strong> {{ $einvoice->irn }}<br>
                            <strong>Ack:</strong> {{ $einvoice->ack_no }} &nbsp; {{ $einvoice->ack_date?->format('d-m-Y H:i') }}
                        </div>
                        <img src="data:image/svg+xml;base64,{{ base64_encode(QrCode::format('svg')->size(90)->margin(0)->errorCorrection('M')->generate($einvoice->signed_qr_code)) }}" width="90" height="90" style="margin-top:4px">
                    </div>
                @endif
            </div>

            <div class="summary-right">
                <table class="totals-table">
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
                    @if ($document->cess_total > 0) <tr><td>Cess</td><td class="right">{{ number_format($document->cess_total, 2) }}</td></tr> @endif
                    @if ($document->shipping_charge > 0) <tr><td>Shipping</td><td class="right">{{ number_format($document->shipping_charge, 2) }}</td></tr> @endif
                    @if ($document->tcs_total > 0) <tr><td>TCS</td><td class="right">{{ number_format($document->tcs_total, 2) }}</td></tr> @endif
                    @if ($document->tds_total > 0) <tr><td>TDS</td><td class="right">-{{ number_format($document->tds_total, 2) }}</td></tr> @endif
                    @if (abs((float)$document->round_off) >= 0.005)
                        <tr><td>Round Off</td><td class="right">{{ number_format($document->round_off, 2) }}</td></tr>
                    @endif
                    <tr class="grand"><td>Grand Total</td><td class="right">₹ {{ number_format($document->grand_total, 2) }}</td></tr>
                    @if ($document->amount_paid > 0)
                        <tr><td>Amount Paid</td><td class="right" style="color:#16a34a">-{{ number_format($document->amount_paid, 2) }}</td></tr>
                    @endif
                    @if ($document->balance_due > 0)
                        <tr class="balance"><td>Balance Due</td><td class="right">₹ {{ number_format($document->balance_due, 2) }}</td></tr>
                    @endif
                </table>

                <div class="signature">
                    <div class="company">For {{ $tenant->legal_name ?: $tenant->name }}</div>
                    @if ($signPath && file_exists($signPath))
                        <img src="{{ $signPath }}" style="max-height:50px; max-width:150px; margin:6px 0">
                    @else
                        <div style="height:50px"></div>
                    @endif
                    <div class="line">Authorised Signatory</div>
                </div>
            </div>
        </div>

        {{-- Terms --}}
        @if ($document->terms)
            <div class="terms" style="margin-top:12px">
                <strong>Terms & Conditions:</strong><br>
                {!! nl2br(e($document->terms)) !!}
            </div>
        @endif

        @if ($document->notes)
            <div class="terms">
                <strong>Notes:</strong> {{ $document->notes }}
            </div>
        @endif
    </div>

    {{-- Footer bar --}}
    @if ($tenant->setting('invoice_footer'))
        <div class="footer-bar">{{ $tenant->setting('invoice_footer') }}</div>
    @else
        <div class="footer-bar">
            {{ $tenant->legal_name ?: $tenant->name }}
            @if ($tenant->setting('contact.phone')) &nbsp;|&nbsp; {{ $tenant->setting('contact.phone') }} @endif
            @if ($tenant->setting('contact.email')) &nbsp;|&nbsp; {{ $tenant->setting('contact.email') }} @endif
        </div>
    @endif
</div>
</body>
</html>