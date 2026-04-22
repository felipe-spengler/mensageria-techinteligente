<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait InteractsWithBridge
{
    private function buildBridgeUrls(): array
    {
        $urls = [
            env('WPP_BRIDGE_URL'),
            'http://bridge:3000',
            'http://127.0.0.1:3000',
            'http://localhost:3000',
        ];

        return array_values(array_filter($urls));
    }

    private function requestBridge(string $path)
    {
        $lastError = null;

        foreach ($this->buildBridgeUrls() as $base) {
            $url = rtrim($base, '/') . '/' . ltrim($path, '/');
            try {
                Log::debug('Bridge request starting', ['path' => $path, 'url' => $url]);
                $response = Http::timeout(30)->get($url);
                Log::debug('Bridge request response', ['path' => $path, 'url' => $url, 'status' => $response->status()]);
                
                if ($response->successful()) {
                    return [$response, $url];
                }

                if ($response->status() === 404) {
                    return [$response, $url];
                }

                $lastError = "HTTP " . $response->status();
                Log::warning('Bridge request returned non-success status', ['path' => $path, 'url' => $url, 'status' => $response->status(), 'body' => $response->body()]);
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error('Bridge request exception', ['path' => $path, 'url' => $url, 'exception' => $e]);
            }

            Log::warning("Bridge request failed [{$path}] {$url}: {$lastError}");
        }

        throw new \RuntimeException('Bridge unreachable: ' . ($lastError ?: 'no URLs available'));
    }
}
