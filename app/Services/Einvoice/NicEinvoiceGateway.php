<?php

namespace App\Services\Einvoice;

use App\Models\Document;
use App\Services\Einvoice\Contracts\EinvoiceGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * NIC IRP / GSP gateway. Auth token is cached (valid ~6h) per GSTIN.
 * Works against the sandbox by pointing EINVOICE_BASE_URL at einv-apisandbox.nic.in.
 */
class NicEinvoiceGateway implements EinvoiceGateway
{
    public function __construct(
        protected EinvoicePayloadBuilder $builder,
        protected array $config,
    ) {}

    protected function endpoint(string $key): string
    {
        return config("einvoice.endpoints.$key");
    }

    protected function token(string $gstin): string
    {
        $cacheKey = 'einv:token:'.md5($this->config['base_url'].$gstin.$this->config['username']);

        return Cache::remember($cacheKey, now()->addMinutes(config('einvoice.token_ttl_minutes', 300)), function () use ($gstin) {
            $res = Http::withHeaders([
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'gstin' => $gstin,
            ])->post($this->config['base_url'].$this->endpoint('auth'), [
                'UserName' => $this->config['username'],
                'Password' => $this->config['password'],
                'AppKey' => base64_encode(random_bytes(32)),
                'ForceRefreshAccessToken' => false,
            ])->throw()->json();

            $token = data_get($res, 'Data.AuthToken');
            if (! $token) {
                throw new RuntimeException('IRP authentication failed: '
                    .(data_get($res, 'ErrorDetails.0.ErrorMessage') ?? json_encode($res)));
            }
            return $token;
        });
    }

    protected function client(string $gstin)
    {
        return Http::baseUrl($this->config['base_url'])
            ->timeout(30)
            ->retry(2, 500)
            ->withHeaders([
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'gstin' => $gstin,
                'user_name' => $this->config['username'],
                'AuthToken' => $this->token($gstin),
            ]);
    }

    public function generateIrn(Document $document): array
    {
        $gstin = $document->tenant->gstin;
        $payload = $this->builder->build($document);
        $res = $this->client($gstin)->post($this->endpoint('generate'), $payload)->json();

        if ((int) data_get($res, 'Status') !== 1) {
            return ['success' => false, 'errors' => data_get($res, 'ErrorDetails', $res), 'request' => $payload];
        }

        return [
            'success' => true,
            'request' => $payload,
            'response' => $res,
            'irn' => data_get($res, 'Data.Irn'),
            'ack_no' => data_get($res, 'Data.AckNo'),
            'ack_date' => data_get($res, 'Data.AckDt'),
            'signed_invoice' => data_get($res, 'Data.SignedInvoice'),
            'signed_qr_code' => data_get($res, 'Data.SignedQRCode'),
        ];
    }

    public function cancelIrn(string $irn, string $reasonCode, string $remarks): array
    {
        $gstin = $this->config['default_gstin'] ?? '';
        $res = $this->client($gstin)->post($this->endpoint('cancel'), [
            'Irn' => $irn, 'CnlRsn' => $reasonCode, 'CnlRem' => $remarks,
        ])->json();

        return ['success' => (int) data_get($res, 'Status') === 1, 'response' => $res];
    }

    public function generateEwayBill(Document $document, array $transport): array
    {
        $gstin = $document->tenant->gstin;
        $irn = $document->einvoice?->irn;

        // Preferred path: generate EWB straight from an existing IRN.
        if ($irn) {
            $res = $this->client($gstin)->post($this->endpoint('ewb_by_irn'), [
                'Irn' => $irn,
                'Distance' => (int) ($transport['distance_km'] ?? 0),
                'TransMode' => (string) ($transport['transport_mode'] ?? '1'),
                'TransId' => $transport['transporter_id'] ?? null,
                'TransName' => $transport['transporter_name'] ?? null,
                'VehNo' => $transport['vehicle_no'] ?? null,
                'VehType' => $transport['vehicle_type'] ?? 'R',
            ])->json();
        } else {
            $res = $this->client($gstin)->post($this->endpoint('ewb_direct'), $this->ewbPayload($document, $transport))->json();
        }

        return [
            'success' => (int) data_get($res, 'Status') === 1,
            'response' => $res,
            'ewb_no' => data_get($res, 'Data.EwbNo'),
            'ewb_date' => data_get($res, 'Data.EwbDt'),
            'valid_until' => data_get($res, 'Data.EwbValidTill'),
        ];
    }

    public function cancelEwayBill(string $ewbNo, string $reasonCode, string $remarks): array
    {
        $gstin = $this->config['default_gstin'] ?? '';
        $res = $this->client($gstin)->post($this->endpoint('ewb_cancel'), [
            'ewbNo' => $ewbNo, 'cancelRsnCode' => $reasonCode, 'cancelRmrk' => $remarks,
        ])->json();

        return ['success' => (int) data_get($res, 'Status') === 1, 'response' => $res];
    }

    /** Verifies saved credentials by performing a live auth call. */
    public function testConnection(string $gstin): array
    {
        try {
            Cache::forget('einv:token:'.md5($this->config['base_url'].$gstin.$this->config['username']));
            $this->token($gstin);

            return ['success' => true, 'message' => 'Authenticated with '.$this->config['base_url']];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Look up a counterparty's registration details from the IRP master. */
    public function fetchGstinDetails(string $sellerGstin, string $lookupGstin): array
    {
        $res = $this->client($sellerGstin)->get($this->endpoint('gstin_details').'/'.$lookupGstin)->json();

        return [
            'success' => (int) data_get($res, 'Status') === 1,
            'legal_name' => data_get($res, 'Data.LegalName'),
            'trade_name' => data_get($res, 'Data.TradeName'),
            'address' => data_get($res, 'Data.AddrBnm'),
            'state_code' => data_get($res, 'Data.StateCode'),
            'status' => data_get($res, 'Data.Status'),
            'block_status' => data_get($res, 'Data.BlkStatus'),
            'raw' => $res,
        ];
    }

    protected function ewbPayload(Document $document, array $transport): array
    {
        return [
            'supplyType' => 'O',
            'subSupplyType' => '1',
            'docType' => 'INV',
            'docNo' => $document->number,
            'docDate' => $document->issue_date->format('d/m/Y'),
            'fromGstin' => $document->tenant->gstin,
            'fromStateCode' => (int) $document->tenant->state_code,
            'toGstin' => $document->party?->gstin ?: 'URP',
            'toStateCode' => (int) ($document->place_of_supply_code ?: 0),
            'totalValue' => (float) $document->taxable_value,
            'cgstValue' => (float) $document->cgst_total,
            'sgstValue' => (float) $document->sgst_total,
            'igstValue' => (float) $document->igst_total,
            'cessValue' => (float) $document->cess_total,
            'totInvValue' => (float) $document->grand_total,
            'transporterId' => $transport['transporter_id'] ?? null,
            'transDistance' => (string) ($transport['distance_km'] ?? 0),
            'transMode' => (string) ($transport['transport_mode'] ?? '1'),
            'vehicleNo' => $transport['vehicle_no'] ?? null,
            'itemList' => $document->lines->map(fn ($l) => [
                'productName' => $l->name,
                'hsnCode' => (int) $l->hsn_sac,
                'quantity' => (float) $l->quantity,
                'qtyUnit' => $l->uqc,
                'taxableAmount' => (float) $l->taxable_value,
                'sgstRate' => (float) ($l->sgst_amount > 0 ? $l->gst_rate / 2 : 0),
                'cgstRate' => (float) ($l->cgst_amount > 0 ? $l->gst_rate / 2 : 0),
                'igstRate' => (float) ($l->igst_amount > 0 ? $l->gst_rate : 0),
            ])->all(),
        ];
    }
}
