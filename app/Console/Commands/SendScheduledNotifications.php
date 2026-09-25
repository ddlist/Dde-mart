<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\ScheduledNotification;
use App\Services\FcmSender;
use Illuminate\Console\Command;

/*
 * DDE-Mart Admin — scheduled push sender (original command).
 * Wire to cron: `* * * * * php artisan schedule:send`. Sends due items via
 * FcmSender and logs each outcome in `notifications`.
 */
class SendScheduledNotifications extends Command
{
    protected $signature = 'schedule:send';

    protected $description = 'Send due scheduled push notifications';

    public function handle(FcmSender $sender): int
    {
        $due = ScheduledNotification::where('status', 'scheduled')
            ->where('send_at', '<=', now())
            ->get();

        foreach ($due as $item) {
            $ok = $sender->sendToTopic($item->audience, $item->subject, $item->message);

            $item->update(['status' => $ok ? 'sent' : 'failed']);

            Notification::create([
                'audience' => $item->audience,
                'subject' => $item->subject,
                'message' => $item->message,
                'status' => $ok ? 'sent' : 'failed',
                'failure' => $ok ? null : 'FCM not configured or send failed',
            ]);

            $this->line("{$item->id}: ".($ok ? 'sent' : 'failed'));
        }

        $this->info("Processed {$due->count()} item(s).");

        return self::SUCCESS;
    }
}
