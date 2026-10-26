<?php
namespace App\Http\Middleware;
use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/' . ltrim($request->path(), '/');

        $redirect = Cache::remember(
            "redirect:{$path}",
            3600,
            fn() => Redirect::findRedirect($path)
        );

        if ($redirect) {
            // hit count رو async آپدیت کن — cache رو نپاک
            Redirect::where('id', $redirect->id)->increment('hit_count');
            return redirect($redirect->to_url, $redirect->status_code);
        }

        return $next($request);
    }
}
