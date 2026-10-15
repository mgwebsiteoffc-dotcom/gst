<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\PushSubscription;
use App\Models\Tenant;
use App\Services\Push\WebPushService;
use Illuminate\Console\Command;

class SendOverdueDigest extends Command
{
    protected $signature = 'push:overdue-digest';
    protected $description = 'Push overdue-bill reminders to vendors with app subscriptions.';

    public function handle(WebPushService $push): int
    {
        if (! $push->enabled()) {
            $this->warn('Web Push not configured — run php artisan push:vapid first.');

            return self::SUCCESS;
        }

        $tenants = Tenant::where('account_status', 'active')
            ->whereHas('documents', fn ($q) => $q->sales()->where('status', 'overdue')->where('balance_due', '>', 0))
            ->get();

        $sent = 0;

        foreach ($tenants as $tenant) {
            $overdue = Document::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->sales()->where('status', 'overdue')->where('balance_due', '>', 0);

            $count = (clone $overdue)->count();
            $total = (float) (clone $overdue)->sum('balance_due');

            $subs = PushSubscription::where('tenant_id', $tenant->id)->get();
            if ($subs->isEmpty()) {
                continue;
            }

            $payload = [
                'title' => config('app.name'),
                'body' => "{$count} overdue bills (₹".number_format($total, 2).') — tap to remind customers',
                'url' => url('/documents/invoice', ['status' => 'overdue']),
                'tag' => 'overdue-digest',
            ];

            foreach ($subs as $sub) {
                try {
                    $r = $push->send($sub->endpoint, $sub->p256dh, $sub->auth, $payload);
                } catch (\Throwable $e) {
                    report($e);
                    continue;
                }

                if ($r['ok']) {
                    $sent++;
                } elseif (in_array($r['status'], [404, 410], true)) {
                    $sub->delete(); // dead subscription
                }
            }
        }

        $this->info("Sent {$sent} push reminders.");

        return self::SUCCESS;
    }
}
