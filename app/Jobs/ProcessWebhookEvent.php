<?php

namespace App\Jobs;

use App\Models\Banking\WebhookEvent;
use App\Models\Banking\Transaction;
use App\Models\Banking\MonitoringAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected WebhookEvent $event
    ) {}

    public function handle(): void
    {
        $this->event->update(['status' => 'processing']);
        $payload = $this->event->payload;

        // Normalize Solarisbank webhook payloads (MUTATION_BOOK events) to standard format
        if (isset($payload['event_type']) && $payload['event_type'] === 'MUTATION_BOOK') {
            $solarisPayload = $payload['payload'] ?? [];
            $status = $solarisPayload['status'] ?? $solarisPayload['booking_status'] ?? 'completed';
            
            if (in_array($status, ['successful', 'successful_booking', 'completed', 'booked'])) {
                $status = 'completed';
            } elseif (in_array($status, ['failed', 'rejected', 'returned'])) {
                $status = 'failed';
            }

            $payload = [
                'external_id' => $payload['resource_id'] ?? $solarisPayload['id'] ?? null,
                'status' => $status,
                'failure_reason' => $solarisPayload['description'] ?? $solarisPayload['failure_reason'] ?? 'Solarisbank mutation booking failed',
            ];
            
            $this->event->event_type = 'transfer';
        }

        try {
            switch ($this->event->event_type) {
                case 'transfer':
                case 'payment':
                    $this->processPaymentEvent($payload);
                    break;

                case 'compliance':
                    $this->processComplianceEvent($payload);
                    break;

                default:
                    Log::warning("ProcessWebhookEvent: Unknown event type: {$this->event->event_type}");
                    break;
            }

            $this->event->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("ProcessWebhookEvent Job Failed: {$e->getMessage()}", [
                'event_id' => $this->event->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->event->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function processPaymentEvent(array $payload): void
    {
        $externalId = $payload['external_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$externalId) {
            throw new \InvalidArgumentException("Webhook payload missing external_id");
        }

        // Find the transaction by metadata external reference
        $transaction = Transaction::where('metadata->baas_external_id', $externalId)->first();

        if (!$transaction) {
            Log::info("ProcessWebhookEvent: No transaction found matching external ID: {$externalId}");
            return;
        }

        if (in_array($transaction->status, ['completed', 'failed'])) {
            Log::info("ProcessWebhookEvent: Transaction {$transaction->id} is already in state: {$transaction->status}");
            return;
        }

        if ($status === 'completed') {
            $transaction->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } elseif ($status === 'failed') {
            $failureReason = $payload['failure_reason'] ?? 'BaaS external transfer failed';
            
            // Revert balances since the transaction failed
            $debitAccount = $transaction->debitAccount;
            if ($debitAccount) {
                $debitAccount->balance += ($transaction->amount + $transaction->fee);
                $debitAccount->available_balance += ($transaction->amount + $transaction->fee);
                $debitAccount->save();
            }

            $transaction->update([
                'status' => 'failed',
                'failure_reason' => $failureReason,
            ]);
        }
    }

    private function processComplianceEvent(array $payload): void
    {
        $severity = $payload['severity'] ?? 'medium';
        $description = $payload['description'] ?? 'Webhook compliance alert';
        $transactionId = $payload['transaction_id'] ?? null;
        $alertType = $payload['alert_type'] ?? 'webhook_alert';

        $userId = null;
        if ($transactionId) {
            $transaction = Transaction::find($transactionId);
            if ($transaction) {
                $userId = $transaction->user_id;
            }
        }

        MonitoringAlert::create([
            'monitoring_rule_id' => null,
            'user_id' => $userId,
            'transaction_id' => $transactionId,
            'alert_type' => $alertType,
            'status' => 'open',
            'severity' => $severity,
            'description' => $description,
        ]);
    }
}
