<?php

namespace App\Http\Middleware;

use App\Models\BannedIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockBannedIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if ($ip && BannedIp::active()->where('ip_address', $ip)->exists()) {
            abort(403, 'Your IP address has been banned.');
        }

        return $next($request);
    }
}
