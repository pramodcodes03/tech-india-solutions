<?php

namespace App\Listeners;

use App\Models\NotificationLog;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Watches Mail events and updates NotificationLog rows that carry our
 * X-Notification-Log-Id header. Only TransactionalNotification mailables
 * stamp this header, so other emails are untouched.
 */
class MarkNotificationLogSent
{
    public function handleSent(MessageSent $event): void
    {
        $id = $this->logIdFrom($event->message);
        if (! $id) {
            return;
        }

        try {
            NotificationLog::where('id', $id)->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error("[Notifications] Failed to mark log {$id} as sent: ".$e->getMessage());
        }
    }

    public function handleSending(MessageSending $event): void
    {
        // No-op for now; reserved for future "started sending" tracking.
    }

    protected function logIdFrom($symfonyMessage): ?int
    {
        if (! $symfonyMessage) {
            return null;
        }
        $headers = method_exists($symfonyMessage, 'getHeaders') ? $symfonyMessage->getHeaders() : null;
        if (! $headers || ! $headers->has('X-Notification-Log-Id')) {
            return null;
        }
        return (int) $headers->get('X-Notification-Log-Id')->getBodyAsString();
    }
}
