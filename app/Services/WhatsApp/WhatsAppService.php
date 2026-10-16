<?php

namespace App\Services\WhatsApp;

use App\Models\Document;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * WhatsApp Business Cloud API integration.
 *
 * Sends messages (text, template, document) via Meta's official API.
 * Each tenant configures their own phone_number_id and access_token
 * in their settings.
 */
class WhatsAppService
{
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('whatsapp.api_url', 'https://graph.facebook.com/v19.0');
    }

    // =========================================================================
    // Configuration
    // =========================================================================

    /**
     * Get the WhatsApp configuration for a tenant.
     */
    public function getConfig(?Tenant $tenant = null): array
    {
        $tenant ??= TenantContext::get();

        return [
            'phone_number_id' => $tenant?->setting('whatsapp.phone_number_id') ?: config('whatsapp.phone_number_id'),
            'access_token' => $tenant?->setting('whatsapp.access_token') ?: config('whatsapp.access_token'),
            'business_name' => $tenant?->setting('whatsapp.business_name') ?: $tenant?->name,
            'language' => $tenant?->setting('whatsapp.language') ?: config('whatsapp.language', 'en'),
            'enabled' => (bool) $tenant?->setting('whatsapp.enabled'),
        ];
    }

    /**
     * Check if WhatsApp is configured and ready to send.
     */
    public function isConfigured(?Tenant $tenant = null): bool
    {
        $config = $this->getConfig($tenant);

        return !empty($config['phone_number_id'])
            && !empty($config['access_token'])
            && $config['enabled'];
    }

    /**
     * Test the WhatsApp API connection by fetching the phone number details.
     */
    public function testConnection(?Tenant $tenant = null): array
    {
        $config = $this->getConfig($tenant);

        if (empty($config['phone_number_id']) || empty($config['access_token'])) {
            return ['success' => false, 'message' => 'Phone Number ID and Access Token are required.'];
        }

        try {
            $response = Http::withToken($config['access_token'])
                ->timeout(15)
                ->get("{$this->apiUrl}/{$config['phone_number_id']}", [
                    'fields' => 'verified_name,display_phone_number,quality_rating',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => "Connected: {$data['display_phone_number']} ({$data['verified_name']})",
                    'data' => $data,
                ];
            }

            $error = $response->json()['error']['message'] ?? 'Unknown error';
            return ['success' => false, 'message' => "API error: {$error}"];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // =========================================================================
    // Send Messages
    // =========================================================================

    /**
     * Send an invoice/quotation to a customer via WhatsApp.
     */
    public function sendDocument(Document $document, ?string $phone = null, ?string $message = null): WhatsAppMessage
    {
        $tenant = TenantContext::get() ?? $document->tenant;
        $config = $this->getConfig($tenant);

        $phone = $phone ?? $document->party?->phone;
        $phone = $this->normalizePhone($phone);

        if (!$phone) {
            throw new \RuntimeException('No phone number available for WhatsApp delivery.');
        }

        // Create message record
        $whatsappMessage = WhatsAppMessage::create([
            'tenant_id' => $tenant->id,
            'messageable_type' => Document::class,
            'messageable_id' => $document->id,
            'to' => $phone,
            'type' => 'text',
            'body' => $message ?? $this->buildDocumentMessage($document),
            'sent_by' => auth()->id(),
            'status' => 'queued',
        ]);

        try {
            // Generate and upload PDF as media
            $mediaId = $this->uploadDocumentPdf($document, $config);

            if ($mediaId) {
                // Send as document message
                $result = $this->sendMediaMessage($config, $phone, $mediaId, $document->filename ?? 'invoice.pdf', $whatsappMessage->body);
                $whatsappMessage->update(['type' => 'document']);
            } else {
                // Fallback to text message with public link
                $result = $this->sendTextMessage($config, $phone, $whatsappMessage->body);
            }

            $messageId = $result['messages'][0]['id'] ?? '';
            $whatsappMessage->markSent($messageId, $result);

            // Update document status if applicable
            if ($document->status === 'draft') {
                $document->update(['status' => 'sent']);
            }
        } catch (\Throwable $e) {
            $whatsappMessage->markFailed($e->getMessage());
            Log::warning('WhatsApp send failed', [
                'document' => $document->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $whatsappMessage;
    }

    /**
     * Send a plain text message.
     */
    public function sendText(Tenant $tenant, string $phone, string $body): WhatsAppMessage
    {
        $config = $this->getConfig($tenant);
        $phone = $this->normalizePhone($phone);

        $whatsappMessage = WhatsAppMessage::create([
            'tenant_id' => $tenant->id,
            'to' => $phone,
            'type' => 'text',
            'body' => $body,
            'sent_by' => auth()->id(),
            'status' => 'queued',
        ]);

        try {
            $result = $this->sendTextMessage($config, $phone, $body);
            $messageId = $result['messages'][0]['id'] ?? '';
            $whatsappMessage->markSent($messageId, $result);
        } catch (\Throwable $e) {
            $whatsappMessage->markFailed($e->getMessage());
            throw $e;
        }

        return $whatsappMessage;
    }

    /**
     * Send a payment reminder for an overdue invoice.
     */
    public function sendPaymentReminder(Document $document, ?string $phone = null): WhatsAppMessage
    {
        $phone = $phone ?? $document->party?->phone;
        $message = $this->buildReminderMessage($document);

        return $this->sendDocument($document, $phone, $message);
    }

    // =========================================================================
    // Internal API Methods
    // =========================================================================

    protected function sendTextMessage(array $config, string $phone, string $body): array
    {
        $response = Http::withToken($config['access_token'])
            ->timeout(30)
            ->post("{$this->apiUrl}/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => ['body' => $body],
            ]);

        if (!$response->successful()) {
            $error = $response->json()['error']['message'] ?? 'WhatsApp API error';
            throw new \RuntimeException($error);
        }

        return $response->json();
    }

    protected function sendMediaMessage(array $config, string $phone, string $mediaId, string $filename, string $caption): array
    {
        $response = Http::withToken($config['access_token'])
            ->timeout(30)
            ->post("{$this->apiUrl}/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'document',
                'document' => [
                    'id' => $mediaId,
                    'filename' => $filename,
                    'caption' => $caption,
                ],
            ]);

        if (!$response->successful()) {
            $error = $response->json()['error']['message'] ?? 'WhatsApp API error';
            throw new \RuntimeException($error);
        }

        return $response->json();
    }

    /**
     * Upload a PDF to WhatsApp and return the media ID.
     */
    protected function uploadDocumentPdf(Document $document, array $config): ?string
    {
        try {
            $document->load('lines.item', 'party');
            $pdf = Pdf::loadView('invoices.pdf', [
                'document' => $document,
                'tenant' => $document->tenant,
            ]);

            $pdfContent = $pdf->output();

            $response = Http::withToken($config['access_token'])
                ->timeout(60)
                ->attach('file', $pdfContent, 'invoice.pdf', ['Content-Type' => 'application/pdf'])
                ->post("{$this->apiUrl}/{$config['phone_number_id']}/media", [
                    'messaging_product' => 'whatsapp',
                    'type' => 'application/pdf',
                ]);

            if ($response->successful()) {
                return $response->json()['id'] ?? null;
            }

            Log::warning('WhatsApp PDF upload failed', ['response' => $response->json()]);
            return null;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp PDF upload error: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // Message Builders
    // =========================================================================

    protected function buildDocumentMessage(Document $document): string
    {
        $tenant = $document->tenant;
        $party = $document->party;
        $publicUrl = URL::signedRoute('documents.public', ['document' => $document->id]);

        // Plain text: WhatsApp messages cannot carry icons, so the layout uses
        // WhatsApp's own *bold* markup and clean labels instead of emojis.
        $lines = [
            "Namaste " . ($party?->name ?: 'ji') . ",",
            "",
            "Your *{$document->title} {$document->number}* from *{$tenant?->name}*.",
            "",
            "*Amount:* ₹" . number_format((float) $document->grand_total, 2),
            "*Date:* " . $document->issue_date->format('d M Y'),
        ];

        if ($document->balance_due > 0 && $document->due_date) {
            $lines[] = "*Due by:* " . $document->due_date->format('d M Y');
        }

        if ($document->balance_due <= 0 && in_array($document->status, ['paid'], true)) {
            $lines[] = "*Status:* Paid";
        }

        $lines[] = "";
        $lines[] = "*View or download:*";
        $lines[] = $publicUrl;
        $lines[] = "";
        $lines[] = "Thank you for your business.";

        return implode("\n", $lines);
    }

    protected function buildReminderMessage(Document $document): string
    {
        $tenant = $document->tenant;
        $party = $document->party;
        $publicUrl = URL::signedRoute('documents.public', ['document' => $document->id]);
        $daysOverdue = $document->due_date ? now()->diffInDays($document->due_date, false) : 0;

        $lines = [
            "Dear " . ($party?->name ?: 'Customer') . ",",
            "",
            "This is a gentle reminder for your pending *{$document->title} {$document->number}*.",
            "",
            "*Balance due:* ₹" . number_format((float) $document->balance_due, 2),
        ];

        if ($daysOverdue > 0) {
            $lines[] = "*Overdue by:* {$daysOverdue} day(s)";
        }

        if ($document->due_date) {
            $lines[] = "*Due date:* " . $document->due_date->format('d M Y');
        }

        $lines[] = "";
        $lines[] = "*View the invoice:*";
        $lines[] = $publicUrl;
        $lines[] = "";
        $lines[] = "Please make the payment at your earliest convenience.";
        $lines[] = "Thank you,";
        $lines[] = "{$tenant?->name}";

        return implode("\n", $lines);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Normalize a phone number for WhatsApp API.
     * Strips non-digits, adds India country code for 10-digit numbers.
     */
    public function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 10) {
            return '91' . $digits;
        }

        if (strlen($digits) >= 11 && strlen($digits) <= 15) {
            return $digits;
        }

        return null;
    }

    /**
     * Get message history for a document.
     */
    public function getHistory(Document $document): \Illuminate\Database\Eloquent\Collection
    {
        return WhatsAppMessage::where('messageable_type', Document::class)
            ->where('messageable_id', $document->id)
            ->latest()
            ->get();
    }
}
