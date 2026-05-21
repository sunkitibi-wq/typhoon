<?php

namespace App\Console\Commands;

use App\Models\Banking\CryptoDeposit;
use App\Models\Banking\CryptoWallet;
use App\Services\BlockchainService;
use Illuminate\Console\Command;

class CheckCryptoDeposits extends Command
{
    protected $signature = 'typhoon:check-crypto-deposits';
    protected $description = 'Check pending crypto deposits against blockchain for confirmations';

    public function handle(BlockchainService $blockchain): int
    {
        if (!$blockchain->isConfigured()) {
            $this->warn('Blockchain provider not configured. Skipping deposit checks.');
            return self::SUCCESS;
        }

        $pendingDeposits = CryptoDeposit::where('status', 'pending')
            ->whereNotNull('tx_hash')
            ->with('cryptoWallet')
            ->get();

        $this->info("Checking {$pendingDeposits->count()} pending deposits...");
        $confirmed = 0;

        foreach ($pendingDeposits as $deposit) {
            try {
                $status = $blockchain->getTransactionStatus($deposit->tx_hash);

                if ($status['status'] === 'confirmed' && $status['confirmations'] >= 0) {
                    $deposit->update([
                        'status' => 'confirmed',
                        'confirmed_at' => now(),
                        'confirmations' => $status['confirmations'],
                    ]);

                    $deposit->cryptoWallet->increment('balance', $deposit->net_amount);
                    $confirmed++;
                    $this->info("Deposit {$deposit->reference} confirmed ({$status['confirmations']} confirmations).");
                } elseif ($status['status'] === 'failed') {
                    $deposit->update(['status' => 'failed']);
                    $this->warn("Deposit {$deposit->reference} failed on chain.");
                } else {
                    $deposit->increment('confirmations');
                }
            } catch (\Exception $e) {
                $this->error("Error checking deposit {$deposit->reference}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Confirmed {$confirmed} deposits.");

        return self::SUCCESS;
    }
}
