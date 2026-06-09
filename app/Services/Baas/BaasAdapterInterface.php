<?php

namespace App\Services\Baas;

interface BaasAdapterInterface
{
    public function initiateCreditTransfer(array $params): array;
    public function initiateDirectDebit(array $params): array;
    public function fetchTransferStatus(string $externalId): array;
}
