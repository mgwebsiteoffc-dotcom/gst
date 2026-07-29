<?php

namespace App\Services\Einvoice;

use App\Models\Document;

/** Builds the NIC IRP schema payload (INV-01, version 1.1). */
class EinvoicePayloadBuilder
{
    public function build(Document $document): array
    {
        // The item relation drives the IsServc flag; load it up front to avoid N+1.
        $document->loadMissing(['tenant', 'party', 'lines.item']);

        $tenant = $document->tenant;
        $party = $document->party;

        $items = $document->lines->values()->map(fn ($l, $i) => [
            'SlNo' => (string) ($i + 1),
            'PrdDesc' => mb_substr($l->name.($l->description ? ' - '.$l->description : ''), 0, 300),
            'IsServc' => $l->item?->type === 'service' ? 'Y' : 'N',
            'HsnCd' => $l->hsn_sac,
            'Qty' => (float) $l->quantity,
            'Unit' => $l->uqc,
            'UnitPrice' => round((float) $l->rate, 3),
            'TotAmt' => round((float) $l->quantity * (float) $l->rate, 2),
            'Discount' => (float) $l->discount_amount,
            'AssAmt' => (float) $l->taxable_value,
            'GstRt' => (float) $l->gst_rate,
            'IgstAmt' => (float) $l->igst_amount,
            'CgstAmt' => (float) $l->cgst_amount,
            'SgstAmt' => (float) $l->sgst_amount,
            'CesRt' => (float) $l->cess_rate,
            'CesAmt' => (float) $l->cess_amount,
            'TotItemVal' => (float) $l->line_total,
        ])->all();

        return [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => match ($document->supply_type) {
                    'SEZ' => $document->with_payment_of_tax ? 'SEZWP' : 'SEZWOP',
                    'EXPORT' => $document->with_payment_of_tax ? 'EXPWP' : 'EXPWOP',
                    'DEEMED_EXPORT' => 'DEXP',
                    default => $party?->gstin ? 'B2B' : 'B2C',
                },
                'RegRev' => $document->reverse_charge ? 'Y' : 'N',
                'IgstOnIntra' => 'N',
            ],
            'DocDtls' => [
                'Typ' => match ($document->doc_type) { 'credit_note' => 'CRN', 'debit_note' => 'DBN', default => 'INV' },
                'No' => $document->number,
                'Dt' => $document->issue_date->format('d/m/Y'),
            ],
            'SellerDtls' => [
                'Gstin' => $tenant->gstin,
                'LglNm' => $tenant->legal_name ?: $tenant->name,
                'Addr1' => (string) $tenant->setting('address.line1', 'NA'),
                'Loc' => (string) $tenant->setting('address.city', 'NA'),
                'Pin' => (int) $tenant->setting('address.pincode', 110001),
                'Stcd' => $tenant->state_code,
            ],
            'BuyerDtls' => [
                'Gstin' => $party?->gstin ?: 'URP',
                'LglNm' => $party?->name ?: 'Unregistered',
                'Pos' => $document->place_of_supply_code,
                'Addr1' => $party?->billing_address ?: 'NA',
                'Loc' => $party?->billing_city ?: 'NA',
                'Pin' => (int) ($party?->billing_pincode ?: 999999),
                'Stcd' => $party?->resolvedStateCode() ?: $document->place_of_supply_code,
            ],
            'ValDtls' => [
                'AssVal' => (float) $document->taxable_value,
                'CgstVal' => (float) $document->cgst_total,
                'SgstVal' => (float) $document->sgst_total,
                'IgstVal' => (float) $document->igst_total,
                'CesVal' => (float) $document->cess_total,
                'OthChrg' => (float) $document->shipping_charge,
                'RndOffAmt' => (float) $document->round_off,
                'TotInvVal' => (float) $document->grand_total,
            ],
            'ItemList' => $items,
        ];
    }
}
