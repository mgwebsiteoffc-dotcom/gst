<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Http;

/**
 * Minimal dependency-free Web Push sender.
 *
 * Implements RFC 8290 (ECDH), RFC 8291 + RFC 8188 (aes128gcm encryption) and
 * RFC 8418 (VAPID auth) on top of ext-openssl + hash only — no composer
 * package needed. A 404/410 from the push endpoint means the subscription
 * is dead and should be deleted by the caller.
 */
class WebPushService
{
    protected ?string $vapidPublic;
    protected ?string $vapidPrivatePem;
    protected string $subject;
    protected int $ttl;

    public function __construct()
    {
        $this->vapidPublic = config('webpush.vapid.public_key') ?: null;
        $this->vapidPrivatePem = config('webpush.vapid.private_key') ?: null;
        $this->subject = config('webpush.vapid.subject', 'mailto:admin@bikribook.com');
        $this->ttl = (int) config('webpush.ttl', 86400);
    }

    public function enabled(): bool
    {
        return (bool) ($this->vapidPublic && $this->vapidPrivatePem);
    }

    /** @return array{ok:bool,status:int} */
    public function send(string $endpoint, string $p256dhB64, string $authB64, array $payload): array
    {
        $cipher = $this->encrypt($p256dhB64, $authB64, json_encode($payload));

        $response = Http::withHeaders([
            'Content-Encoding' => 'aes128gcm',
            'TTL' => (string) $this->ttl,
            'Authorization' => 'vapid t='.$this->vapidToken($endpoint).', k='.$this->vapidPublic,
        ])->withBody($cipher, 'application/octet-stream')->post($endpoint);

        return ['ok' => $response->successful(), 'status' => $response->status()];
    }

    /** Single-record aes128gcm encryption (record size 4096). */
    public function encrypt(string $p256dhB64, string $authB64, string $plaintext): string
    {
        $uaPublic = $this->b64urlDecode($p256dhB64);
        $authSecret = $this->b64urlDecode($authB64);

        if (strlen($uaPublic) !== 65 || strlen($authSecret) !== 16) {
            throw new \InvalidArgumentException('Invalid push subscription keys.');
        }
        if (strlen($plaintext) > 4000) {
            throw new \InvalidArgumentException('Push payload too large.');
        }
        if (! function_exists('openssl_pkey_derive')) {
            throw new \RuntimeException('openssl_pkey_derive() is required for Web Push (PHP 8+).');
        }

        // Ephemeral ECDH keypair (P-256).
        $ephem = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $details = openssl_pkey_get_details($ephem);
        $asPublic = "\x04"
            .str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT)
            .str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);

        $shared = openssl_pkey_derive($this->pemFromPoint($uaPublic), $ephem, 32);

        // RFC 8291: ikm = Expand(Extract(auth_secret, ecdh_secret), key_info).
        $keyInfo = 'WebPush: info'."\x00".$uaPublic.$asPublic;
        $ikm = $this->hkdfExpand(hash_hmac('sha256', $shared, $authSecret, true), $keyInfo, 32);

        // RFC 8188: fresh salt per message, then cek + nonce.
        $salt = random_bytes(16);
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $cek = $this->hkdfExpand($prk, "Content-Encoding: aes128gcm\x00", 16);
        $nonce = $this->hkdfExpand($prk, "Content-Encoding: nonce\x00", 12);

        $recordSize = 4096;
        $block = str_pad($plaintext."\x02", $recordSize - 16, "\x00", STR_PAD_RIGHT);

        $tag = '';
        $ciphertext = openssl_encrypt($block, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($ciphertext === false || strlen($tag) !== 16) {
            throw new \RuntimeException('Web Push encryption failed.');
        }

        return $salt.pack('N', $recordSize).chr(65).$asPublic.$ciphertext.$tag;
    }

    /** Short-lived VAPID JWT (ES256) for one push endpoint's origin. */
    protected function vapidToken(string $endpoint): string
    {
        $parts = parse_url($endpoint);
        $aud = $parts['scheme'].'://'.$parts['host'].(! empty($parts['port']) ? ':'.$parts['port'] : '');

        $head = $this->b64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $body = $this->b64url(json_encode([
            'aud' => $aud, 'exp' => time() + 43200, 'sub' => $this->subject,
        ]));

        $key = openssl_pkey_get_private($this->vapidPrivatePem);
        if (! $key || ! openssl_sign($head.'.'.$body, $der, $key, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('VAPID signing failed — check VAPID_PRIVATE_KEY.');
        }

        return $head.'.'.$body.'.'.$this->b64url($this->derToRaw($der));
    }

    /** ASN.1 DER ECDSA signature → raw R||S (JWS format). */
    protected function derToRaw(string $der): string
    {
        $p = 0;
        if (ord($der[$p++]) !== 0x30) {
            throw new \RuntimeException('Bad VAPID signature.');
        }
        $len = ord($der[$p++]);
        if ($len & 0x80) {
            $p += $len & 0x7F;
        }
        if (ord($der[$p++]) !== 0x02) {
            throw new \RuntimeException('Bad VAPID signature.');
        }
        $rLen = ord($der[$p++]);
        $r = substr($der, $p, $rLen);
        $p += $rLen;
        if (ord($der[$p++]) !== 0x02) {
            throw new \RuntimeException('Bad VAPID signature.');
        }
        $sLen = ord($der[$p++]);
        $s = substr($der, $p, $sLen);

        return str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT)
            .str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
    }

    /** Raw 65-byte uncompressed P-256 point → PEM SubjectPublicKeyInfo. */
    protected function pemFromPoint(string $point): string
    {
        $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$point;

        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($der), 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }

    protected function hkdfExpand(string $prk, string $info, int $len): string
    {
        $okm = '';
        $t = '';
        $i = 0;
        while (strlen($okm) < $len) {
            $i++;
            $t = hash_hmac('sha256', $t.$info.chr($i), $prk, true);
            $okm .= $t;
        }

        return substr($okm, 0, $len);
    }

    protected function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    protected function b64urlDecode(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/'));
    }
}
