<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');
        
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized. Bearer token required.'], 401);
        }

        $key = str_replace('Bearer ', '', $header);
        $apiKey = \App\Models\ApiKey::where('key', $key)->first();

        if (!$apiKey) {
            return response()->json(['error' => 'Invalid API Key.'], 401);
        }

        if ($apiKey->status !== 'active') {
            return response()->json(['error' => 'API Key is ' . $apiKey->status . '.'], 403);
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return response()->json(['error' => 'API Key expired on ' . $apiKey->expires_at->format('Y-m-d')], 403);
        }

        // Share the API Key model with the request for later use
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
