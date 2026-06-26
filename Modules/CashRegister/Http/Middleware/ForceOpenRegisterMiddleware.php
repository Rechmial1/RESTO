<?php

namespace Modules\CashRegister\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Modules\CashRegister\Services\RegisterForceOpenService;

class ForceOpenRegisterMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only enforce for HTML page requests (avoid JSON/assets like manifest)
        $accept = $request->headers->get('Accept', '');
        $isHtml = stripos($accept, 'text/html') !== false;
        if (!$isHtml) {
            return $next($request);
        }

        // Skip middleware for certain paths to avoid infinite redirects
        $skipPaths = [
            'cash-register/cashier',
            'cash-register/export/*',
            'cash-register/print/*',
            'logout',
            'login',
            'auth/logout',
            'auth/login',
            'manifest.json',
        ];
        
        foreach ($skipPaths as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }
        
        // Enforce redirect if user should force-open and has no open session
        if (Auth::check()) {
            $user = Auth::user();

            $shouldForce = RegisterForceOpenService::shouldForceOpenRegister($user);
            $hasOpen = RegisterForceOpenService::hasOpenRegisterSession($user);

            if ($shouldForce && !$hasOpen) {
                // Capture intended URL to return after opening
                // Avoid storing manifest.json or non-HTML as intended
                if ($request->is('manifest.json')) {
                    Session::forget('intended_after_register');
                } else {
                    Session::put('intended_after_register', $request->fullUrl());
                }

                // Prefer session flag if already set, else route helper
                $redirectUrl = Session::get('force_open_register')
                    ?: RegisterForceOpenService::getForceOpenRedirectUrl();

                return redirect($redirectUrl);
            }
        }

        return $next($request);
    }
}