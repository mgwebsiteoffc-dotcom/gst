<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature = 'push:vapid';
    protected $description = 'Generate a VAPID key pair for Web Push notifications.';

    public function handle(): int
    {
        $res = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);

        if (! $res || ! openssl_pkey_export($res, $pem)) {
            $this->error('Could not generate an EC key (ext-openssl with EC support is required).');

            return self::FAILURE;
        }

        $d = openssl_pkey_get_details($res);
        $pub = "\x04"
            .str_pad($d['ec']['x'], 32, "\x00", STR_PAD_LEFT)
            .str_pad($d['ec']['y'], 32, "\x00", STR_PAD_LEFT);

        // .env double-quoted values interpret \n, so the key stays on one line.
        $oneLine = str_replace("\n", '\n', trim($pem));

        $this->info('Add these two lines to your .env (keep the quotes!):');
        $this->line('');
        $this->line('VAPID_PUBLIC_KEY='.rtrim(strtr(base64_encode($pub), '+/', '-_'), '='));
        $this->line('VAPID_PRIVATE_KEY="'.$oneLine.'"');

        return self::SUCCESS;
    }
}
