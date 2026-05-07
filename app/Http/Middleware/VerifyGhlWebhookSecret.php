<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyGhlWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.ghl.bridge_webhook_secret');

        abort_if($expected === '', 500, 'GHL webhook secret is not configured.');

        $incoming = (string) $request->header('X-Bridge-Secret', '');
        abort_if($incoming === '' || ! hash_equals($expected, $incoming), 401, 'Invalid GHL webhook secret.');

        return $next($request);
    }
}
