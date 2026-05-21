<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BaasService
{
    private string $driver;
    private ?string $apiKey;
    private ?string $secret;
    private string $baseUrl;

    public function __construct()
    {
        $this->driver = config('services.baas.driver', 'mock');
        $this->apiKey = config('services.baas.api_key');
        $this->secret = config('services.baas.secret');
        $this->baseUrl = config('services.baas.base_url', 'https://api.sandbox.baas-provider.com');
    }

    public function initiateCreditTransfer(array $params): array
    {
        if ($this->driver === 'mock') {
            Log::info('BaaS: Initiated credit transfer (Mock)', $params);
            return [
                'success' => true,
                'external_id' => 'tx_' . Str::random(24),
                'status' => 'pending',
                'fee' => 0.15,
            ];
        }

        return $this->sendRequest('/v1/transfers', 'POST', $params);
    }

    public function initiateDirectDebit(array $params): array
    {
        if ($this->driver === 'mock') {
            Log::info('BaaS: Initiated direct debit (Mock)', $params);
            return [
                'success' => true,
                'external_id' => 'dd_' . Str::random(24),
                'status' => 'pending',
                'fee' => 0.20,
            ];
        }

        return $this->sendRequest('/v1/direct-debits', 'POST', $params);
    }

    public function fetchTransferStatus(string $externalId): array
    {
        if ($this->driver === 'mock') {
            return [
                'success' => true,
                'external_id' => $externalId,
                'status' => 'completed',
            ];
        }

        return $this->sendRequest("/v1/transfers/{$externalId}", 'GET');
    }

    private function sendRequest(string $endpoint, string $method, array $data = []): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($this->apiKey) {
            $headers['X-API-Key'] = $this->apiKey;
        }

        if ($this->secret) {
            $headers['X-API-Secret'] = $this->secret;
        }

        try {
            $response = Http::withHeaders($headers)->send($method, $url, [
                'json' => $data,
            ]);

            if ($response->failed()) {
                Log::error("BaaS Request Failed: {$method} {$url} | Status: {$response->status()}", [
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error' => "BaaS server returned status code: {$response->status()}",
                ];
            }

            $json = $response->json();
            return array_merge(['success' => true], $json ?? []);
        } catch (\Exception $e) {
            Log::error("BaaS Request Exception: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
