<?php
namespace App\Services;

use App\Models\PushNotification;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use App\Models\GlobalSetting;

class PushNotificationService
{
    public function sendPushNotification($userId, $message, $url = null)
    {
        // Fetch the user's push subscription
        $subscriptions = PushNotification::where('user_id', $userId)->get();

        if ($subscriptions->isEmpty()) {
            return response()->json(['error' => 'No subscriptions found'], 404);
        }
        $settings = GlobalSetting::first();
        if (!$settings || !$settings->vapid_public_key || !$settings->vapid_private_key) {
            return response()->json(['error' => 'VAPID keys not configured'], 500);
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $settings->vapid_subject ?? 'mailto:admin@example.com',
                'publicKey' => $settings->vapid_public_key,
                'privateKey' => $settings->vapid_private_key,
            ],
        ]);
        
        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
            ]);

        $webPush->queueNotification($subscription, json_encode([
                'title' => restaurant()->name,
                'body' => $message,
                'icon' => restaurant()->logo_url,
                'badge' => '/icons/badge-72x72.png',
                'data' => [
                    'url' => $url ?? '/'
                ]
            ]));
           
        }

        // Send the notifications and log errors
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                info("Push notification sent successfully to: " . $report->getRequest()->getUri());
            } else {
                info("Push notification failed: " . $report->getReason());
            }
        }

        return response()->json(['success' => true]);
    }
}
