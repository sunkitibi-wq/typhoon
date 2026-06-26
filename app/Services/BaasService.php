<?php

namespace App\Services;

use App\Services\Baas\SolarisbankAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BaasService
{
    private string $driver;
    private $adapter;

    public function __construct()
    {
        $this->driver = config('services.baas.driver', 'mock');

        if ($this->driver === 'solarisbank') {
            $this->adapter = new SolarisbankAdapter();
        }
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

        if ($this->adapter) {
            return $this->adapter->initiateCreditTransfer($params);
        }

        return [
            'success' => false,
            'error' => "BaaS driver [{$this->driver}] is not configured/supported.",
        ];
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

        if ($this->adapter) {
            return $this->adapter->initiateDirectDebit($params);
        }

        return [
            'success' => false,
            'error' => "BaaS driver [{$this->driver}] is not configured/supported.",
        ];
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

        if ($this->adapter) {
            return $this->adapter->fetchTransferStatus($externalId);
        }

        return [
            'success' => false,
            'error' => "BaaS driver [{$this->driver}] is not configured/supported.",
        ];
    }

    public function createPerson(array $params): array
    {
        if ($this->driver === 'mock') {
            return [
                'success' => true,
                'id' => 'person_' . Str::random(10),
                'first_name' => $params['first_name'] ?? 'John',
                'last_name' => $params['last_name'] ?? 'Doe',
            ];
        }

        if ($this->adapter) {
            return $this->adapter->createPerson($params);
        }

        return [
            'success' => false,
            'error' => "BaaS driver [{$this->driver}] is not configured/supported.",
        ];
    }

    public function createAccount(array $params): array
    {
        if ($this->driver === 'mock') {
            // Generate a random valid-looking IBAN for the account
            $iban = 'DE' . random_int(10, 99) . '37040044' . str_pad((string)random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
            return [
                'success' => true,
                'id' => 'acc_' . Str::random(10),
                'iban' => $iban,
                'currency' => $params['currency'] ?? 'EUR',
            ];
        }

        if ($this->adapter) {
            return $this->adapter->createAccount($params);
        }

        return [
            'success' => false,
            'error' => "BaaS driver [{$this->driver}] is not configured/supported.",
        ];
    }

    public function createCard(array $params): array
    {
        if ($this->driver === 'mock') {
            $maskedPan = '4*** **** **** ' . random_int(1000, 9999);
            $expiry = now()->addYears(3)->format('m/y');
            return [
                'success' => true,
                'id' => 'card_' . Str::random(10),
                'type' => $params['type'] ?? 'virtual',
                'cardholder_name' => $params['cardholder_name'] ?? 'John Doe',
                'masked_pan' => $maskedPan,
                'expiration_date' => $expiry,
                'status' => 'active',
            ];
        }

        if ($this->adapter) {
            return $this->adapter->createCard($params);
        }

        return [
            'success' => false,
            'error' => "BaaS driver [{$this->driver}] is not configured/supported.",
        ];
    }

    public function updateCardStatus(string $externalCardId, string $status): array
    {
        if ($this->driver === 'mock') {
            return [
                'success' => true,
                'id' => $externalCardId,
                'status' => $status === 'blocked' ? 'blocked' : ($status === 'active' ? 'active' : 'closed'),
            ];
        }

        if ($this->adapter) {
            return $this->adapter->updateCardStatus($externalCardId, $status);
        }

        return [
            'success' => false,
            'error' => "BaaS driver [{$this->driver}] is not configured/supported.",
        ];
    }
}
