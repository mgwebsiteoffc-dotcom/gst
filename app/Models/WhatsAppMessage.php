<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'template_params' => 'array',
            'response' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function messageable()
    {
        return $this->morphTo();
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isSent(): bool
    {
        return in_array($this->status, ['sent', 'delivered', 'read'], true);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markSent(string $whatsappMessageId, array $response = []): void
    {
        $this->update([
            'status' => 'sent',
            'whatsapp_message_id' => $whatsappMessageId,
            'response' => $response,
            'sent_at' => now(),
        ]);
    }

    public function markDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    public function markFailed(string $error, array $response = []): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'response' => $response,
            'retry_count' => $this->retry_count + 1,
        ]);
    }
}
