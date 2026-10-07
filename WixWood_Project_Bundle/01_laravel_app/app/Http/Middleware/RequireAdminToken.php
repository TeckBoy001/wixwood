<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Protects every /api/admin/* route. Simple on purpose — this is a
// furniture showcase site, not a multi-user SaaS admin, so one shared
// secret (checked against config('services.wixwood_admin.token')) is
// enough. Swap this for real Laravel auth later if WixWood ever needs
// more than one admin account.
class RequireAdminToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.wixwood_admin.token');
        $given = $request->header('x-admin-token') ?? $request->query('token');

        if (! $expected || $given !== $expected) {
            return response()->json(['error' => 'Invalid or missing admin token.'], 401);
        }

        return $next($request);
    }
}
