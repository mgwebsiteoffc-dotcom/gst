@php
    $seller = config('billing.seller');
    $split = $payment->taxSplit();
    $money = fn ($paise) => number_format($paise / 100, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $payment->invoice_number }}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; margin: 0; padding: 28px; }
    h1 { font-size: 18px; margin: 0 0 2px; }
    .muted { color: #64748b; }
    .top { display: table; width: 100%; margin-bottom: 18px; }
    .top .left, .top .right { display: table-cell; vertical-align: top; }
    .top .right { text-align: right; }
    .badge { display: inline-block; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 8px; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; }
    .parties { margin: 14px 0 18px; }
    .parties td { vertical-align: top; width: 50%; padding-right: 12px; }
    .label { text-transform: uppercase; letter-spacing: .06em; font-size: 9px; color: #94a3b8; margin-bottom: 3px; }
    .items th { text-align: left; background: #f1f5f9; padding: 7px 8px; font-size: 10px; text-transform: uppercase; color: #64748b; }
    .items td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
    .num { text-align: right; }
    .totals { margin-top: 12px; width: 60%; margin-left: 40%; }
    .totals td { padding: 3px 8px; }
    .totals tr.grand td { border-top: 1px solid #cbd5e1; font-weight: bold; font-size: 13px; padding-top: 6px; }
    .footer { margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 10px; color: #64748b; }
</style>
</head>
<body>
    <div class="top">
        <div class="left">
            <h1>{{ $seller['legal_name'] ?: $seller['name'] }}</h1>
            <div class="muted">
                @if ($seller['address']){{ $seller['address'] }}<br>@endif
                @if ($seller['gstin'])GSTIN: {{ $seller['gstin'] }}<br>@endif
                @if ($seller['email']){{ $seller['email'] }}@endif
                @if ($seller['phone']) · {{ $seller['phone'] }}@endif
            </div>
        </div>
        <div class="right">
            <div class="badge">{{ $payment->status === 'paid' ? 'TAX INVOICE' : strtoupper($payment->status) }}</div>
            <p style="margin:8px 0 0"><strong>{{ $payment->invoice_number }}</strong></p>
            <p class="muted" style="margin:2px 0 0">{{ ($payment->paid_at ?? $payment->created_at)->format('d M Y') }}</p>
        </div>
    </div>

    <table class="parties">
        <tr>
            <td>
                <div class="label">Billed to</div>
                <strong>{{ $tenant->legal_name ?: $tenant->name }}</strong><br>
                <span class="muted">
                    @if ($tenant->gstin)GSTIN: {{ $tenant->gstin }}<br>@endif
                    @if ($tenant->state_code)Place of supply: {{ $tenant->state_code }}<br>@endif
                    {{ $tenant->setting('address.city') }}
                </span>
            </td>
            <td>
                <div class="label">Subscription period</div>
                {{ $payment->period_start?->format('d M Y') }} → {{ $payment->period_end?->format('d M Y') }}<br>
                <span class="muted">Interval: {{ ucfirst($payment->interval) }}</span>
                @if ($payment->gateway_payment_id)
                    <br><span class="muted">Payment ref: {{ $payment->gateway_payment_id }}</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:12%">SAC</th>
                <th>Description</th>
                <th class="num" style="width:16%">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $seller['sac'] }}</td>
                <td>
                    {{ $seller['name'] }} subscription — {{ $payment->plan_name }} plan
                    ({{ $payment->interval }})<br>
                    <span class="muted">{{ $payment->period_start?->format('d M Y') }} to {{ $payment->period_end?->format('d M Y') }}</span>
                </td>
                <td class="num">{{ $money($payment->amount_paise) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Taxable value</td><td class="num">{{ $money($payment->amount_paise) }}</td></tr>
        @if ($split['interstate'])
            <tr><td>IGST</td><td class="num">{{ $money($split['igst']) }}</td></tr>
        @else
            <tr><td>CGST</td><td class="num">{{ $money($split['cgst']) }}</td></tr>
            <tr><td>SGST</td><td class="num">{{ $money($split['sgst']) }}</td></tr>
        @endif
        <tr class="grand"><td>Total</td><td class="num">{{ $payment->currency }} {{ $money($payment->total_paise) }}</td></tr>
    </table>

    <div class="footer">
        {{ config('billing.terms') }}<br>
        This is a computer-generated invoice for subscription services.
    </div>
</body>
</html>
