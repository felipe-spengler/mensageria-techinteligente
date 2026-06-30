<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($request, 500, $duration, $e->getMessage());
            throw $e;
        }

        $duration = round((microtime(true) - $startTime) * 1000);
        $statusCode = $response->getStatusCode();
        
        $errorMsg = null;
        if ($statusCode >= 400) {
            $data = json_decode($response->getContent(), true);
            $errorMsg = $data['error'] ?? $data['message'] ?? 'Unknown Error';
        }

        $this->logRequest($request, $statusCode, $duration, $errorMsg);

        return $response;
    }

    private function logRequest(Request $request, int $statusCode, int $duration, ?string $errorMsg = null): void
    {
        $apiKey = $request->attributes->get('api_key');
        $apiKeyLabel = $apiKey ? 'KeyID:' . $apiKey->id : 'Guest';
        $ip = $request->ip();
        $method = $request->method();
        $path = $request->path();
        
        // Target number for send route
        $to = $request->input('to') ?? 'N/A';
        
        // Timestamp with milliseconds (Brasília Time)
        $now = now('America/Sao_Paulo')->format('Y-m-d H:i:s.v');

        $logLine = sprintf(
            "[%s] IP: %s | %s | %s %s | Target: %s | Status: %d | Duration: %dms%s",
            $now,
            $ip,
            $apiKeyLabel,
            $method,
            $path,
            $to,
            $statusCode,
            $duration,
            $errorMsg ? " | Error: {$errorMsg}" : ""
        );

        try {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/api_access.log'),
            ])->info($logLine);
        } catch (\Throwable $e) {
            Log::info("[API ACCESS FALLBACK] " . $logLine);
        }
    }
}
