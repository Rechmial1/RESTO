<?php

namespace Modules\CashRegister\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\CashRegister\Services\RegisterForceOpenService;

class EnforceRegisterOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {


            $user = auth()->user();

            // Only enforce for GET HTML requests
            $isHtmlGet = $request->method() === 'GET' && ! $request->expectsJson();

            $isCashRegisterRoute = $request->routeIs('cashregister.*');

            if (
                $isHtmlGet &&
                ! $isCashRegisterRoute &&
                user_can('Open Cash Register') &&
                RegisterForceOpenService::shouldForceOpenRegister($user) &&
                ! RegisterForceOpenService::hasOpenRegisterSession($user)
            ) {
                // Save intended URL and redirect to cashier page
                session(['intended_after_register' => $request->fullUrl()]);
                return redirect()->to(RegisterForceOpenService::getForceOpenRedirectUrl());
            }
        }

        return $next($request);
    }
}
