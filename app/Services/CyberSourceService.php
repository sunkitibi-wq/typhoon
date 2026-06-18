<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CyberSourceService
{
    private ?string $baseUrl;
    private ?string $merchantId;
    private ?string $apiKeyId;
    private ?string $sharedSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.cybersource.base_url');
        $this->merchantId = config('services.cybersource.merchant_id');
        $this->apiKeyId = config('services.cybersource.api_key_id');
        $this->sharedSecret = config('services.cybersource.shared_secret');
    }

    /**
     * Charge card using a transient Flex Microform token
     */
    public function chargeToken(string $transientToken, float $amount, string $currency, array $billingDetails): array
    {
        // Fallback to mock simulation if credentials are missing or placeholders
        if (
            empty($this->apiKeyId) ||
            empty($this->sharedSecret) ||
            empty($this->merchantId) ||
            $this->apiKeyId === 'your_api_key_id' ||
            $this->sharedSecret === 'your_shared_secret_key' ||
            $this->merchantId === 'your_merchant_id'
        ) {
            Log::info('CyberSource credentials not set. Simulating successful card payment.', [
                'amount' => $amount,
                'currency' => $currency,
                'token' => $transientToken,
            ]);

            return [
                'status' => 'AUTHORIZED_AND_CAPTURED',
                'id' => 'MOCK-CS-' . strtoupper(bin2hex(random_bytes(8))),
                'clientReferenceInformation' => [
                    'code' => 'DEP-CS-' . strtoupper(bin2hex(random_bytes(6))),
                ],
                'orderInformation' => [
                    'amountDetails' => [
                        'totalAmount' => number_format($amount, 2, '.', ''),
                        'currency' => $currency,
                    ],
                ],
            ];
        }

        $payload = [
            'clientReferenceInformation' => [
                'code' => 'DEP-CS-' . strtoupper(bin2hex(random_bytes(6)))
            ],
            'processingInformation' => [
                'capture' => true,
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => number_format($amount, 2, '.', ''),
                    'currency' => $currency,
                ],
                'billTo' => [
                    'firstName' => $billingDetails['first_name'] ?? '',
                    'lastName' => $billingDetails['last_name'] ?? '',
                    'address1' => $billingDetails['address_line1'] ?? '',
                    'locality' => $billingDetails['city'] ?? '',
                    'postalCode' => $billingDetails['postal_code'] ?? '',
                    'country' => $billingDetails['country'] ?? '',
                    'email' => $billingDetails['email'] ?? '',
                ],
            ],
            'tokenInformation' => [
                'transientToken' => $transientToken
            ]
        ];

        return $this->sendRequest('POST', '/pts/v2/payments', $payload);
    }

    /**
     * Send signed request to CyberSource API
     */
    private function sendRequest(string $method, string $resource, array $payload): array
    {
        $url = $this->baseUrl . $resource;
        $dateTime = gmdate('D, d M Y H:i:s \G\M\T');
        $payloadJson = json_encode($payload);
        
        $digest = 'SHA-256=' . base64_encode(hash('sha256', $payloadJson, true));
        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        
        $signatureString = "host: $host\n" .
                           "date: $dateTime\n" .
                           "request-target: " . strtolower($method) . " $resource\n" .
                           "v-c-merchant-id: {$this->merchantId}\n" .
                           "digest: $digest";

        $signature = base64_encode(hash_hmac('sha256', $signatureString, base64_decode($this->sharedSecret), true));
        $headersList = 'host date request-target v-c-merchant-id digest';
        
        $signatureHeader = sprintf(
            'keyid="%s", algorithm="HmacSHA256", headers="%s", signature="%s"',
            $this->apiKeyId,
            $headersList,
            $signature
        );

        $response = Http::withHeaders([
            'v-c-merchant-id' => $this->merchantId,
            'Date' => $dateTime,
            'Host' => $host,
            'Signature' => $signatureHeader,
            'Digest' => $digest,
            'Content-Type' => 'application/json',
        ])->send($method, $url, ['body' => $payloadJson]);

        if ($response->failed()) {
            Log::error('CyberSource Payment API failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \RuntimeException('Payment processing failed: ' . ($response->json('message') ?? 'Unknown Gateway Error'));
        }

        return $response->json();
    }
}
