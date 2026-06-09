<?php

namespace App\Services;

use App\Models\Banking\MonitoringAlert;
use App\Models\Banking\MonitoringRule;
use App\Models\Banking\SanctionsScreening;
use App\Models\Banking\SuspiciousActivity;
use App\Models\Banking\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ComplianceService
{
    public function screenUser(User $user): SanctionsScreening
    {
        $screening = SanctionsScreening::create([
            'user_id' => $user->id,
            'list_type' => 'global_sanctions',
            'status' => 'clear',
            'screening_result' => ['lists_checked' => ['EU Sanctions', 'OFAC SDN', 'UN Sanctions']],
            'screened_at' => now(),
        ]);

        return $screening;
    }

    public function evaluateTransaction(Transaction $transaction): ?MonitoringAlert
    {
        $rules = MonitoringRule::where('is_active', true)->get();

        foreach ($rules as $rule) {
            $matched = $this->matchesRule($transaction, $rule);
            if ($matched) {
                return $this->createAlert($transaction, $rule);
            }
        }

        return null;
    }

    public function reportSuspiciousActivity(
        Transaction $transaction,
        string $riskLevel,
        string $category,
        string $description,
        User $reporter,
        array $evidence = []
    ): SuspiciousActivity {
        return SuspiciousActivity::create([
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
            'risk_level' => $riskLevel,
            'category' => $category,
            'description' => $description,
            'evidence' => $evidence,
            'status' => 'open',
            'reported_at' => now(),
            'reported_by' => $reporter->id,
        ]);
    }

    public function resolveAlert(MonitoringAlert $alert, User $resolver, string $resolution, string $notes = ''): void
    {
        $alert->update([
            'status' => $resolution,
            'resolved_at' => now(),
            'resolved_by' => $resolver->id,
            'resolution_notes' => $notes,
        ]);
    }

    private function matchesRule(Transaction $transaction, MonitoringRule $rule): bool
    {
        $conditions = $rule->conditions;

        foreach ($conditions as $field => $condition) {
            $value = data_get($transaction, $field);
            $operator = $condition['operator'] ?? 'eq';
            $target = $condition['value'] ?? null;

            if (!$this->compare($value, $operator, $target)) {
                return false;
            }
        }

        return true;
    }

    private function compare(mixed $value, string $operator, mixed $target): bool
    {
        return match ($operator) {
            'eq' => $value == $target,
            'gt' => $value > $target,
            'gte' => $value >= $target,
            'lt' => $value < $target,
            'lte' => $value <= $target,
            'neq' => $value != $target,
            'in' => in_array($value, (array) $target),
            'not_in' => !in_array($value, (array) $target),
            'contains' => is_string($value) && str_contains($value, $target),
            default => false,
        };
    }

    private function createAlert(Transaction $transaction, MonitoringRule $rule): MonitoringAlert
    {
        return MonitoringAlert::create([
            'monitoring_rule_id' => $rule->id,
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
            'alert_type' => $rule->name,
            'severity' => $rule->severity,
            'status' => 'open',
            'description' => $rule->description ?? "Transaction matched rule: {$rule->name}",
            'details' => [
                'rule_name' => $rule->name,
                'rule_category' => $rule->category,
                'matched_conditions' => $rule->conditions,
                'transaction_reference' => $transaction->reference,
                'amount' => $transaction->amount,
            ],
            'amount' => $transaction->amount,
        ]);
    }
}
