<?php

namespace App\Observers;

use App\Models\PushNotification;

class PushNotificationObserver
{

    public function creating(PushNotification $model)
    {
        if (restaurant()) {
            $model->restaurant_id = restaurant()->id;
        }
    }
}
