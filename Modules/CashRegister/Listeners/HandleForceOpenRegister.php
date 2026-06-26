<?php

namespace Modules\CashRegister\Listeners;

use Illuminate\Auth\Events\Login;
use Modules\CashRegister\Services\RegisterForceOpenService;

class HandleForceOpenRegister
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        
        // Check if user should be forced to open register after login
        if (RegisterForceOpenService::shouldForceOpenRegister($user) && 
            !RegisterForceOpenService::hasOpenRegisterSession($user)) {
            
            $redirectUrl = RegisterForceOpenService::getForceOpenRedirectUrl();
            session(['force_open_register' => $redirectUrl]);
        }
    }
}
