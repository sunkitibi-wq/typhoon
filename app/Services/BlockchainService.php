<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use kornrunner\Ethereum\Transaction as EthereumTransaction;
use kornrunner\Keccak;
use kornrunner\Serializer\HexPrivateKeySerializer;
use Mdanter\Ecc\Curves\CurveFactory;
use Mdanter\Ecc\Curves\SecgCurve;

class BlockchainService
{
    private readonly string $provider;
    private readonly string $network;
    private readonly ?string $projectId;
    private readonly ?string $apiKey;
    private readonly ?string $metamaskApiKey;

    public function __construct()
    {
        $this->provider = config('blockchain.default', 'mock');
        $this->network = config("blockchain.providers.{$this->provider}.network", 'sepolia');
        $this->projectId = config('blockchain.providers.infura.project_id');
        $this->apiKey = config('blockchain.providers.alchemy.api_key');
        $this->metamaskApiKey = config('blockchain.providers.metamask.api_key');
    }

    public function generateAddress(): array
    {
        if ($this->provider === 'mock') {
            return [
                'address' => '0x' . Str::random(40),
                'private_key' => '0x' . Str::random(64),
            ];
        }

        return $this->generateLocalKeyPair();
    }

    public function getBalance(string $address): string
    {
        if ($this->provider === 'mock') {
            return '0';
        }

        return $this->callRpc('eth_getBalance', [$address, 'latest']);
    }

    public function sendTransaction(string $privateKey, string $toAddress, ?string $valueInWei = null, string $hexData = '0x', string $gasLimit = '0x5208', ?string $customGasPrice = null): string
    {
        if ($this->provider === 'mock') {
            return '0x' . Str::random(64);
        }

        $fromAddress = $this->addressFromPrivateKey($privateKey);
        $txCount = $this->callRpc('eth_getTransactionCount', [$fromAddress, 'pending']);
        $gasPrice = $customGasPrice ?? $this->callRpc('eth_gasPrice', []);

        $transaction = new EthereumTransaction(
            $txCount,
            $gasPrice,
            $gasLimit,
            $toAddress,
            $valueInWei ?? '0x0',
            $hexData,
        );

        $raw = '0x' . $transaction->getRaw($this->stripHexPrefix($privateKey), $this->getChainId());

        return $this->callRpc('eth_sendRawTransaction', [$raw]);
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

        if ($this->provider === 'metamask' && $this->metamaskApiKey) {
            return true;
        }

        return false;
    }

    private function generateLocalKeyPair(): array
    {
        $privateKey = bin2hex(random_bytes(32));
        $address = $this->addressFromPrivateKey($privateKey);

        return [
            'address' => $address,
            'private_key' => '0x' . $privateKey,
        ];
    }

    private function addressFromPrivateKey(string $privateKey): string
    {
        $privateKey = $this->stripHexPrefix($privateKey);

        $generator = CurveFactory::getGeneratorByName(SecgCurve::NAME_SECP_256K1);
        $serializer = new HexPrivateKeySerializer($generator);
        $privateKeyObject = $serializer->parse($privateKey);
        $publicKey = $privateKeyObject->getPublicKey();
        $point = $publicKey->getPoint();

        $x = $this->hexup(gmp_strval($point->getX(), 16));
        $y = $this->hexup(gmp_strval($point->getY(), 16));

        $publicKeyHex = $x . $y;
        $address = Keccak::hash(hex2bin($publicKeyHex), 256);

        return '0x' . substr($address, -40);
    }

    private function stripHexPrefix(string $value): string
    {
        return strtolower(str_replace('0x', '', $value));
    }

    private function hexup(string $value): string
    {
        return strlen($value) % 2 === 0 ? $value : "0{$value}";
    }

    private function getChainId(): int
    {
        return match (true) {
            str_contains($this->network, 'mainnet') => 1,
            str_contains($this->network, 'sepolia') => 11155111,
            str_contains($this->network, 'goerli') => 5,
            str_contains($this->network, 'rinkeby') => 4,
            str_contains($this->network, 'kovan') => 42,
            default => 1,
        };
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
        $endpoint = str_replace('{api_key}', $this->provider === 'metamask' ? ($this->metamaskApiKey ?? '') : ($this->apiKey ?? ''), $endpoint);
        $endpoint = str_replace('{network}', $this->network, $endpoint);

        return $endpoint;
    }
}
