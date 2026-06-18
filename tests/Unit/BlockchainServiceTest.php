<?php

namespace Tests\Unit;

use App\Services\BlockchainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BlockchainServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_address_returns_valid_ethereum_keypair(): void
    {
        Config::set('blockchain.default', 'mock');

        $service = $this->app->make(BlockchainService::class);

        $keyPair = $service->generateAddress();

        $this->assertArrayHasKey('address', $keyPair);
        $this->assertArrayHasKey('private_key', $keyPair);
        $this->assertStringStartsWith('0x', $keyPair['address']);
        $this->assertStringStartsWith('0x', $keyPair['private_key']);
        $this->assertEquals(66, strlen($keyPair['private_key']));
        $this->assertEquals(42, strlen($keyPair['address']));
    }

    public function test_send_transaction_uses_infura_rpc_and_returns_tx_hash(): void
    {
        Config::set('blockchain.default', 'infura');
        Config::set('blockchain.providers.infura.project_id', 'test');
        Config::set('blockchain.providers.infura.project_secret', null);
        Config::set('blockchain.providers.infura.network', 'sepolia');

        Http::fake(function ($request) {
            $payload = json_decode($request->body(), true);
            $method = $payload['method'] ?? null;

            return Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => match ($method) {
                    'eth_getTransactionCount' => '0x0',
                    'eth_gasPrice' => '0x4a817c800',
                    'eth_sendRawTransaction' => '0xdeadbeef',
                    default => null,
                },
            ], 200);
        });

        $service = $this->app->make(BlockchainService::class);
        $keyPair = $service->generateAddress();

        $txHash = $service->sendTransaction(
            $keyPair['private_key'],
            '0x0000000000000000000000000000000000000001',
            '0x0',
            '0x',
            '0x5208',
            '0x4a817c800'
        );

        $this->assertSame('0xdeadbeef', $txHash);
    }

    public function test_send_transaction_uses_metamask_rpc_and_returns_tx_hash(): void
    {
        Config::set('blockchain.default', 'metamask');
        Config::set('blockchain.providers.metamask.api_key', 'test_key');
        Config::set('blockchain.providers.metamask.network', 'sepolia');

        Http::fake(function ($request) {
            $payload = json_decode($request->body(), true);
            $method = $payload['method'] ?? null;

            $this->assertStringContainsString('https://sepolia.infura.io/v3/test_key', $request->url());

            return Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => match ($method) {
                    'eth_getTransactionCount' => '0x0',
                    'eth_gasPrice' => '0x4a817c800',
                    'eth_sendRawTransaction' => '0xdeadbeef_metamask',
                    default => null,
                },
            ], 200);
        });

        $service = $this->app->make(BlockchainService::class);
        $keyPair = $service->generateAddress();

        $txHash = $service->sendTransaction(
            $keyPair['private_key'],
            '0x0000000000000000000000000000000000000001',
            '0x0',
            '0x',
            '0x5208',
            '0x4a817c800'
        );

        $this->assertSame('0xdeadbeef_metamask', $txHash);
    }
}
