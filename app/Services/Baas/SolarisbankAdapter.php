<?php

namespace App\Services\Baas;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SolarisbankAdapter implements BaasAdapterInterface
{
    private string $apiKey;
    private string $secret;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.baas.api_key', '');
        $this->secret = config('services.baas.secret', '');
        $this->baseUrl = config('services.baas.base_url', 'https://api.sandbox.solarisbank.de');
    }

    public function initiateCreditTransfer(array $params): array
    {
        $personId = $params['external_person_id'] ?? null;
        $accountId = $params['external_account_id'] ?? null;

        if (!$personId || !$accountId) {
            return [
                'success' => false,
                'error' => 'Missing external_person_id or external_account_id for Solarisbank transfer.',
            ];
        }

        $endpoint = "/v1/persons/{$personId}/accounts/{$accountId}/transactions/sepa_credit_transfer";

        // Amount value converted to sub-units (e.g. cents) if necessary. 
        // Typically Solaris uses float decimals or integer sub-units. We will pass value and currency.
        $payload = [
            'amount' => [
                'value' => (float) $params['amount'],
                'currency' => $params['currency'] ?? 'EUR',
            ],
            'recipient_iban' => $params['creditor_iban'],
            'recipient_bic' => $params['creditor_bic'] ?? '',
            'recipient_name' => $params['creditor_name'],
            'reference' => $params['remittance_info'] ?? '',
        ];

        return $this->sendRequest($endpoint, 'POST', $payload);
    }

    public function initiateDirectDebit(array $params): array
    {
        $personId = $params['external_person_id'] ?? null;
        $accountId = $params['external_account_id'] ?? null;

        if (!$personId || !$accountId) {
            return [
                'success' => false,
                'error' => 'Missing external_person_id or external_account_id for Solarisbank direct debit.',
            ];
        }

        $endpoint = "/v1/persons/{$personId}/accounts/{$accountId}/transactions/sepa_direct_debit";

        $payload = [
            'amount' => [
                'value' => (float) $params['amount'],
                'currency' => $params['currency'] ?? 'EUR',
            ],
            'debtor_iban' => $params['debtor_iban'],
            'debtor_bic' => $params['debtor_bic'] ?? '',
            'debtor_name' => $params['debtor_name'],
            'reference' => $params['remittance_info'] ?? '',
            'mandate_reference' => $params['mandate_reference'] ?? '',
        ];

        return $this->sendRequest($endpoint, 'POST', $payload);
    }

    public function fetchTransferStatus(string $externalId): array
    {
        // For general status checking, we can retrieve transfer state
        return $this->sendRequest("/v1/transfers/{$externalId}", 'GET');
    }

    public function createPerson(array $params): array
    {
        $payload = [
            'first_name' => $params['first_name'] ?? '',
            'last_name' => $params['last_name'] ?? '',
            'email' => $params['email'] ?? '',
            'birth_date' => $params['date_of_birth'] ?? '',
            'nationality' => $params['nationality'] ?? '',
            'country_of_residence' => $params['country'] ?? '',
            'mobile_number' => $params['phone_number'] ?? '',
            'address' => [
                'line_1' => $params['address_line1'] ?? '',
                'line_2' => $params['address_line2'] ?? '',
                'postal_code' => $params['postal_code'] ?? '',
                'city' => $params['city'] ?? '',
                'country' => $params['country'] ?? '',
            ],
        ];

        return $this->sendRequest('/v1/persons', 'POST', $payload);
    }

    public function createAccount(array $params): array
    {
        $personId = $params['external_person_id'] ?? null;
        if (!$personId) {
            return [
                'success' => false,
                'error' => 'Missing external_person_id for Solarisbank account creation.',
            ];
        }

        $payload = [
            'currency' => $params['currency'] ?? 'EUR',
            'type' => 'checking',
        ];

        return $this->sendRequest("/v1/persons/{$personId}/accounts", 'POST', $payload);
    }

    public function createCard(array $params): array
    {
        $personId = $params['external_person_id'] ?? null;
        $accountId = $params['external_account_id'] ?? null;

        if (!$personId || !$accountId) {
            return [
                'success' => false,
                'error' => 'Missing external_person_id or external_account_id for card creation.',
            ];
        }

        $payload = [
            'type' => $params['type'] ?? 'virtual',
            'cardholder_name' => $params['cardholder_name'] ?? '',
        ];

        return $this->sendRequest("/v1/persons/{$personId}/accounts/{$accountId}/cards", 'POST', $payload);
    }

    public function updateCardStatus(string $externalCardId, string $status): array
    {
        if ($status === 'blocked') {
            return $this->sendRequest("/v1/cards/{$externalCardId}/block", 'POST');
        } elseif ($status === 'active') {
            return $this->sendRequest("/v1/cards/{$externalCardId}/unblock", 'POST');
        } elseif ($status === 'closed') {
            return $this->sendRequest("/v1/cards/{$externalCardId}/close", 'POST');
        }

        return [
            'success' => false,
            'error' => "Unsupported status transition: {$status}",
        ];
    }

    private function getAccessToken(): ?string
    {
        return Cache::remember('solarisbank:oauth_token', 3000, function () {
            try {
                $response = Http::asForm()->post(rtrim($this->baseUrl, '/') . '/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->apiKey,
                    'client_secret' => $this->secret,
                ]);

                if ($response->failed()) {
                    Log::error("Solarisbank OAuth request failed: " . $response->body());
                    return null;
                }

                return $response->json()['access_token'] ?? null;
            } catch (\Exception $e) {
                Log::error("Solarisbank OAuth exception: " . $e->getMessage());
                return null;
            }
        });
    }

    private function sendRequest(string $endpoint, string $method, array $data = []): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $token = $this->getAccessToken();

        if (!$token) {
            return [
                'success' => false,
                'error' => 'Unable to authenticate with Solarisbank (OAuth token generation failed).',
            ];
        }

        try {
            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->send($method, $url, empty($data) ? [] : ['json' => $data]);

            if ($response->failed()) {
                Log::error("Solarisbank API Request Failed: {$method} {$url} | Status: {$response->status()}", [
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error' => "Solarisbank returned status code: {$response->status()} - " . $response->body(),
                ];
            }

            $json = $response->json();
            return array_merge(['success' => true], $json ?? []);
        } catch (\Exception $e) {
            Log::error("Solarisbank Request Exception: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
