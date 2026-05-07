<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictSubaccountRoutes
{
    /**
     * @var array<int, string>
     */
    private array $allowedRouteNames = [
        'online-payments',
        'online-payments.orders.store',
        'online-payments.charge',
        'account-settings',
        'account-settings.nmi.update',
        'account-settings.nmi.subscribe',
        'profile.edit',
        'profile.update',
        'profile.destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isSubaccountUser()) {
            return $next($request);
        }

        $routeName = (string) optional($request->route())->getName();
        if ($routeName !== '' && in_array($routeName, $this->allowedRouteNames, true)) {
            return $next($request);
        }

        return redirect()->route('online-payments');
    }
}
