<?php

namespace App\Http\Controllers;

use App\Models\PushNotification;
use Illuminate\Http\Request;
use Minishlink\WebPush\Subscription;

class PushNotificationController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.auth' => 'required',
            'keys.p256dh' => 'required',
        ]);

        // Add logging to see the data coming through
        PushNotification::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'user_id' => auth()->id(),
                'public_key' => $request->keys['p256dh'],
                'auth_token' => $request->keys['auth'],
            ]
        );
        return response()->json(['success' => true]);
    }

}
