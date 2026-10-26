<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BlockUnsafeAdminToolsInProduction
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $routeName = (string) ($request->route()?->getName() ?? '');
            $path = '/'.trim($request->path(), '/');

            foreach (['file-manager', 'robots-editor', 'sitemap-manager'] as $blocked) {
                if (str_contains($routeName, $blocked) || str_contains($path, $blocked)) {
                    abort(404);
                }
            }
        }

        return $next($request);
    }
}
