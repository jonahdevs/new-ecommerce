<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCartIsNotEmpty
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(CartService::class)->hasItems()) {
            return redirect()
                ->route('cart')
                ->with('error', 'Your cart is empty.');
        }

        return $next($request);
    }
}
