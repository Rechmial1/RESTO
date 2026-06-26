<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\GlobalSetting;
use Minishlink\WebPush\VAPID;

class WebPushSettings extends Component
{
    use LivewireAlert;

    public $public_key, $private_key, $subject;

    public function mount()
    {
        $settings = GlobalSetting::first();
        $this->public_key = $settings->vapid_public_key ?? '';
        $this->private_key = $settings->vapid_private_key ?? '';
        $this->subject = $settings->vapid_subject ?? 'mailto:admin@example.com';
    }

    public function generateKeys()
    {
        $vapid = VAPID::createVapidKeys();

        $this->public_key = $vapid['publicKey'];
        $this->private_key = $vapid['privateKey'];

        GlobalSetting::first()->update([
            'vapid_public_key' => $this->public_key,
            'vapid_private_key' => $this->private_key,
        ]);

        cache()->forget('global_setting');

        $this->alert('success', __('messages.vapidGenerated'));
    }

    public function save()
    {
        $this->validate([
            'public_key' => 'required|string',
            'private_key' => 'required|string',
            'subject' => 'required|string',
        ]);

        GlobalSetting::first()->update([
            'vapid_public_key' => $this->public_key,
            'vapid_private_key' => $this->private_key,
            'vapid_subject' => $this->subject,
        ]);

        $this->alert('success', __('messages.settingsUpdated'));
    }

    public function render()
    {
        return view('livewire.settings.web-push-settings');
    }
}
