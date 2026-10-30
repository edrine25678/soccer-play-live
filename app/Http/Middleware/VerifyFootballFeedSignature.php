<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyFootballFeedSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('football.feed_secret', '');

        if (strlen($secret) < 32) {
            return response()->json(['message' => 'The signed fixture feed is not configured.'], 503);
        }

        $timestamp = $request->header('X-Football-Timestamp', '');
        $signature = $request->header('X-Football-Signature', '');

        if (
            ! is_string($timestamp)
            || ! preg_match('/\A\d{10}\z/', $timestamp)
            || abs(now()->timestamp - (int) $timestamp) > 300
            || ! hash_equals('sha256='.hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret), $signature)
        ) {
            return response()->json(['message' => 'The fixture feed signature is invalid.'], 401);
        }

        return $next($request);
    }
}
