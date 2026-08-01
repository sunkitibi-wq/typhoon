<?php

namespace App\Console\Commands;

use App\Models\Banking\StandingOrder;
use App\Services\TransactionRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessStandingOrders extends Command
{
    protected $signature = 'typhoon:process-standing-orders';

    protected $description = 'Execute standing orders that are due';

    public function handle(TransactionRouter $router): int
    {
        $dueOrders = StandingOrder::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->where('next_execution_at', '<=', now())
                    ->orWhere('ends_at', '<', now());
            })
            ->with(['debitAccount', 'user', 'beneficiary'])
            ->get();

        if ($dueOrders->isEmpty()) {
            $this->info('No standing orders due.');

            return self::SUCCESS;
        }

        $this->info("Processing {$dueOrders->count()} due standing order(s)...");
        $executed = 0;
        $skipped = 0;

        foreach ($dueOrders as $order) {
            DB::transaction(function () use ($order, $router, &$executed, &$skipped) {
                $fresh = StandingOrder::whereKey($order->id)->lockForUpdate()->first();

                if (! $fresh || $fresh->status !== 'active') {
                    return;
                }

                if ($fresh->ends_at && $fresh->ends_at->lt(now())) {
                    $fresh->update(['status' => 'completed', 'next_execution_at' => now()]);
                    $this->info("Standing order #{$fresh->id} completed (end date reached).");

                    return;
                }

                if ($fresh->next_execution_at === null || $fresh->next_execution_at->gt(now())) {
                    return;
                }

                $account = $fresh->debitAccount;

                if (! $account || $account->status !== 'active') {
                    $this->warn("Standing order #{$fresh->id}: debit account unavailable, skipping.");
                    $fresh->advanceToNextCycle();
                    $skipped++;

                    return;
                }

                $iban = $fresh->beneficiary_iban ?? $fresh->beneficiary?->iban ?? $fresh->beneficiary?->account_number;

                if (! $iban) {
                    $this->warn("Standing order #{$fresh->id}: missing beneficiary IBAN, skipping.");
                    $fresh->advanceToNextCycle();
                    $skipped++;

                    return;
                }

                try {
                    $router->route($fresh->user, $account, [
                        'beneficiary_name' => $fresh->beneficiary_name ?? $fresh->beneficiary?->name ?? 'Beneficiary',
                        'beneficiary_iban' => $iban,
                        'beneficiary_bic' => $fresh->beneficiary_bic ?? $fresh->beneficiary?->bic,
                        'amount' => (float) $fresh->amount,
                        'currency' => $fresh->currency,
                        'remittance_info' => $fresh->notes ?? $fresh->reference ?? "Standing order #{$fresh->id}",
                    ]);

                    $fresh->update(['last_executed_at' => now()]);
                    $fresh->advanceToNextCycle();

                    $executed++;
                    $this->info("Standing order #{$fresh->id} executed ({$fresh->currency} {$fresh->amount}).");
                } catch (\Exception $e) {
                    $fresh->advanceToNextCycle();

                    Log::warning("Standing order #{$fresh->id} execution failed", [
                        'error' => $e->getMessage(),
                        'user_id' => $fresh->user_id,
                    ]);

                    $skipped++;
                    $this->error("Standing order #{$fresh->id} failed: {$e->getMessage()}");
                }
            });
        }

        $this->newLine();
        $this->info("Executed {$executed}, skipped {$skipped}.");

        return self::SUCCESS;
    }
}
