<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite que Custom Page de GoHighLevel cargue esta app en iframe.
 * @see https://marketplace.gohighlevel.com/docs/marketplace-modules/CustomPages
 */
class AllowEmbeddedFrameFromGhl
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $origins = config('services.ghl.embed_frame_ancestors', []);

        if (! is_array($origins) || $origins === []) {
            return $response;
        }

        $response->headers->remove('X-Frame-Options');

        $ancestorList = implode(' ', $origins);
        $directives = 'frame-ancestors '.$ancestorList;

        $existing = $response->headers->get('Content-Security-Policy');
        if ($existing !== null && $existing !== '' && ! str_contains($existing, 'frame-ancestors')) {
            $response->headers->set('Content-Security-Policy', $existing.'; '.$directives);
        } else {
            $response->headers->set('Content-Security-Policy', $directives);
        }

        return $response;
    }
}
