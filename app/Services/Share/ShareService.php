<?php

namespace App\Services\Share;

use App\Models\Document;
use Illuminate\Support\Str;

/**
 * Sharing helpers: WhatsApp deep links and UPI payment intents.
 *
 * These are the two channels Indian SMEs actually use to get paid, and both
 * work without any third-party integration or fees.
 */
class ShareService
{
    /** wa.me deep link with a pre-written message and the public invoice URL. */
    public function whatsappUrl(Document $document): string
    {
        $phone = preg_replace('/\D/', '', (string) $document->party?->phone);

        // wa.me needs the country code; assume India when a bare 10-digit number is stored.
        if (strlen($phone) === 10) {
            $phone = '91'.$phone;
        }

        $lines = [
            'Namaste '.($document->party?->name ?: 'ji').',',
            '',
            $document->title.' '.$document->number.' for '.$this->money($document->grand_total).'.',
        ];

        if ($document->balance_due > 0 && $document->due_date) {
            $lines[] = 'Due by '.$document->due_date->format('d M Y').'.';
        }

        $lines[] = '';
        $lines[] = 'View or download: '.$this->publicUrl($document);
        $lines[] = '';
        $lines[] = 'Thank you,';
        $lines[] = $document->tenant?->name;

        $text = rawurlencode(implode("\n", array_filter($lines, fn ($l) => $l !== null)));

        return $phone
            ? "https://wa.me/{$phone}?text={$text}"
            : "https://wa.me/?text={$text}";
    }

    /**
     * Polite payment reminder via wa.me — works without the Business API.
     * Returns null when the party has no phone number stored.
     */
    public function reminderWhatsappUrl(Document $document): ?string
    {
        $phone = preg_replace('/\D/', '', (string) $document->party?->phone);

        if (! $phone) {
            return null;
        }

        if (strlen($phone) === 10) {
            $phone = '91'.$phone;
        }

        $lines = [
            'Namaste '.($document->party?->name ?: 'ji').',',
            '',
            'Gentle reminder: '.$this->money($document->balance_due).' is pending for '.
            $document->title.' '.$document->number.' dated '.$document->issue_date->format('d M Y').'.',
            '',
            'View or pay: '.$this->publicUrl($document),
            '',
            'Thank you,',
            $document->tenant?->name,
        ];

        return 'https://wa.me/'.$phone.'?text='.rawurlencode(implode("\n", $lines));
    }

    /**
     * UPI intent URI (NPCI spec). Scanning this opens any UPI app with the
     * amount and reference pre-filled.
     */
    public function upiUri(Document $document): ?string
    {
        $vpa = $document->tenant?->setting('bank.upi');

        if (! $vpa) {
            return null;
        }

        return 'upi://pay?'.http_build_query([
            'pa' => $vpa,
            'pn' => $document->tenant->legal_name ?: $document->tenant->name,
            'am' => number_format((float) $document->balance_due, 2, '.', ''),
            'cu' => 'INR',
            'tn' => Str::limit($document->number, 35, ''),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Signed, expiring public link so a customer can view without logging in. */
    public function publicUrl(Document $document): string
    {
        return url()->signedRoute('documents.public', ['document' => $document->id]);
    }

    public function mailtoUrl(Document $document): string
    {
        $subject = rawurlencode($document->title.' '.$document->number.' from '.$document->tenant?->name);
        $body = rawurlencode(
            "Dear ".($document->party?->name ?: 'Customer').",\n\n".
            "Please find your ".strtolower($document->title)." ".$document->number.
            " for ".$this->money($document->grand_total).".\n\n".
            "View it here: ".$this->publicUrl($document)."\n\n".
            "Regards,\n".$document->tenant?->name
        );

        return 'mailto:'.($document->party?->email ?? '')."?subject={$subject}&body={$body}";
    }

    protected function money(float|string $v): string
    {
        return '₹'.number_format((float) $v, 2);
    }
}
