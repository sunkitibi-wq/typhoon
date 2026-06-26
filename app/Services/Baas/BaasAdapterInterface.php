<?php

namespace App\Services\Baas;

interface BaasAdapterInterface
{
    public function initiateCreditTransfer(array $params): array;
    public function initiateDirectDebit(array $params): array;
    public function fetchTransferStatus(string $externalId): array;
    public function createPerson(array $params): array;
    public function createAccount(array $params): array;
    public function createCard(array $params): array;
    public function updateCardStatus(string $externalCardId, string $status): array;
}
