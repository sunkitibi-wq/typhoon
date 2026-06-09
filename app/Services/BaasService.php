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
}
