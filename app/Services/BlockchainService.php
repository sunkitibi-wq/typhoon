<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BlockchainService
{
    private readonly string $provider;
    private readonly string $network;
    private readonly ?string $projectId;
    private readonly ?string $apiKey;

    public function __construct()
    {
        $this->provider = config('blockchain.default', 'mock');
        $this->network = config('blockchain.providers.infura.network', 'sepolia');
        $this->projectId = config('blockchain.providers.infura.project_id');
        $this->apiKey = config('blockchain.providers.alchemy.api_key');
    }

    public function generateAddress(): array
    {
        if ($this->provider === 'mock') {
            return [
                'address' => '0x' . Str::random(40),
                'private_key' => '0x' . Str::random(64),
            ];
        }

        return $this->generateAddressViaRpc();
    }

    public function getBalance(string $address): string
    {
        if ($this->provider === 'mock') {
            return '0';
        }

        return $this->callRpc('eth_getBalance', [$address, 'latest']);
    }

    public function sendTransaction(string $fromAddress, string $toAddress, ?string $valueInWei = null, string $hexData = '0x', string $network = 'ethereum'): string
    {
        if ($this->provider === 'mock') {
            return '0x' . Str::random(64);
        }

        $txCount = $this->callRpc('eth_getTransactionCount', [$fromAddress, 'pending']);
        $gasPrice = $this->callRpc('eth_gasPrice', []);

        $tx = [
            'from' => $fromAddress,
            'to' => $toAddress,
            'data' => $hexData,
            'gas' => '0x5208',
            'gasPrice' => $gasPrice,
            'nonce' => $txCount,
        ];

        if ($valueInWei) {
            $tx['value'] = $valueInWei;
        }

        return $this->callRpc('eth_sendTransaction', [$tx]);
    }

    public function getTransactionStatus(string $txHash): array
    {
        if ($this->provider === 'mock') {
            return [
                'status' => 'confirmed',
                'block_number' => '0x' . dechex(rand(1000000, 2000000)),
                'confirmations' => 12,
            ];
        }

        $receipt = $this->callRpc('eth_getTransactionReceipt', [$txHash]);

        if (!$receipt) {
            return ['status' => 'pending', 'confirmations' => 0];
        }

        $blockNumber = hexdec($receipt['blockNumber'] ?? '0x0');
        $latestBlock = hexdec($this->callRpc('eth_blockNumber', []));
        $confirmations = $latestBlock - $blockNumber;
        $status = ($receipt['status'] ?? '0x0') === '0x1' ? 'confirmed' : 'failed';

        return [
            'status' => $status,
            'block_number' => $receipt['blockNumber'] ?? null,
            'confirmations' => max(0, $confirmations),
            'gas_used' => $receipt['gasUsed'] ?? null,
        ];
    }

    public function watchDeposits(string $address, int $minConfirmations = 12): array
    {
        if ($this->provider === 'mock') {
            return [];
        }

        $latestBlock = hexdec($this->callRpc('eth_blockNumber', []));
        $fromBlock = '0x' . dechex(max(0, $latestBlock - 5000));
        $toBlock = 'latest';

        $logs = $this->callRpc('eth_getLogs', [[
            'address' => $address,
            'fromBlock' => $fromBlock,
            'toBlock' => $toBlock,
        ]]);

        if (!is_array($logs)) {
            return [];
        }

        return array_filter($logs, function ($log) use ($minConfirmations) {
            $blockNumber = hexdec($log['blockNumber'] ?? '0x0');
            $latestBlock = hexdec($this->callRpc('eth_blockNumber', []));
            return ($latestBlock - $blockNumber) >= $minConfirmations;
        });
    }

    public function isConfigured(): bool
    {
        if ($this->provider === 'mock') {
            return false;
        }

        if ($this->provider === 'infura' && $this->projectId) {
            return true;
        }

        if ($this->provider === 'alchemy' && $this->apiKey) {
            return true;
        }

        return false;
    }

    private function generateAddressViaRpc(): array
    {
        $result = $this->callRpc('personal_newAccount', ['']);
        return [
            'address' => $result,
            'private_key' => null,
        ];
    }

    private function callRpc(string $method, array $params = []): mixed
    {
        $endpoint = $this->buildEndpoint();
        $payload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ];

        $headers = ['Content-Type' => 'application/json'];

        if ($this->provider === 'infura' && config('blockchain.providers.infura.project_secret')) {
            $headers['Authorization'] = 'Basic ' . base64_encode(
                config('blockchain.providers.infura.project_secret') . ':'
            );
        }

        $response = Http::withHeaders($headers)->post($endpoint, $payload);

        if ($response->failed()) {
            throw new \RuntimeException("Blockchain RPC call failed: {$response->body()}");
        }

        $json = $response->json();

        if (isset($json['error'])) {
            throw new \RuntimeException("Blockchain RPC error: {$json['error']['message']}");
        }

        return $json['result'] ?? null;
    }

    private function buildEndpoint(): string
    {
        $provider = config("blockchain.providers.{$this->provider}");

        if (!$provider) {
            throw new \RuntimeException("Unknown blockchain provider: {$this->provider}");
        }

        $endpoint = $provider['endpoint'];
        $endpoint = str_replace('{project_id}', $this->projectId ?? '', $endpoint);
        $endpoint = str_replace('{api_key}', $this->apiKey ?? '', $endpoint);
        $endpoint = str_replace('{network}', $this->network, $endpoint);

        return $endpoint;
    }
}
