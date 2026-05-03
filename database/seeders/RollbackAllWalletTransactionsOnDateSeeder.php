<?php

namespace Database\Seeders;

use App\Models\Commission;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\TokenWallet;
use App\Models\TokenTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class RollbackAllWalletTransactionsOnDateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Rollback and cancel all wallet and token wallet transactions made on a specific date,
     * then recalculate all balances from scratch.
     */
    public function run(): void
    {
        $targetDate = '2026-05-03'; // Change this to your target date

        $this->command->info("Starting complete rollback for all wallet transactions on date: {$targetDate}");

        DB::transaction(function () use ($targetDate) {
            // === REGULAR WALLET TRANSACTIONS ROLLBACK ===

            // Get all wallet transactions created on the target date
            $walletTransactions = WalletTransaction::whereDate('created_at', $targetDate)->get();

            if ($walletTransactions->isNotEmpty()) {
                $this->command->info("Found {$walletTransactions->count()} regular wallet transactions to rollback");

                foreach ($walletTransactions as $transaction) {
                    // Get the wallet associated with this transaction
                    $wallet = Wallet::find($transaction->wallet_id);

                    if (!$wallet) {
                        $this->command->error("Wallet not found for transaction ID: {$transaction->id}");
                        continue;
                    }

                    // Delete the transaction completely
                    $transaction->delete();
                    $this->command->info("Deleted wallet transaction ID {$transaction->id} ({$transaction->transaction_type}) for member {$wallet->member_id}");
                }
            } else {
                $this->command->info("No regular wallet transactions found for date: {$targetDate}");
            }

            // === TOKEN WALLET TRANSACTIONS ROLLBACK ===

            // Get all token transactions created on the target date
            $tokenTransactions = TokenTransaction::whereDate('created_at', $targetDate)->get();

            if ($tokenTransactions->isNotEmpty()) {
                $this->command->info("Found {$tokenTransactions->count()} token transactions to rollback");

                foreach ($tokenTransactions as $transaction) {
                    // Get the token wallet associated with this transaction
                    $tokenWallet = TokenWallet::find($transaction->token_wallet_id);

                    if (!$tokenWallet) {
                        $this->command->error("Token wallet not found for transaction ID: {$transaction->id}");
                        continue;
                    }

                    // Delete the token transaction completely
                    $transaction->delete();
                    $this->command->info("Deleted token transaction ID {$transaction->id} ({$transaction->transaction_type}) for member {$tokenWallet->member_id}");
                }
            } else {
                $this->command->info("No token transactions found for date: {$targetDate}");
            }

            // === RECALCULATE ALL BALANCES ===

            $this->command->info("Starting complete balance recalculation...");

            // Recalculate regular wallet balances
            $this->recalculateRegularWalletBalances();

            // Recalculate token wallet balances
            $this->recalculateTokenWalletBalances();
        });

        $this->command->info("Complete wallet rollback and recalculation finished for date: {$targetDate}");
    }

    /**
     * Recalculate all regular wallet balances from scratch
     */
    private function recalculateRegularWalletBalances(): void
    {
        $this->command->info("Recalculating regular wallet balances...");

        $wallets = Wallet::all();

        foreach ($wallets as $wallet) {
            $memberId = $wallet->member_id;

            // Calculate total direct commissions for this member
            $totalDirectCommissions = Commission::where('sponsor_id', $memberId)
                ->where('commission_type', 'direct')
                ->sum('commission_value');

            // Calculate total internal transfers sent - to subtract
            $totalTransfersSent = WalletTransaction::where('sender_member_id', $memberId)
                ->where('transaction_type', 'send_internal_transfer')
                ->where('status', 'accepted')
                ->sum('amount');

            // Calculate net internal transfers (received - sent)
            $netInternalTransfers = $totalTransfersSent;

            // Calculate other transactions
            $totalDeposits = WalletTransaction::whereHas('wallet', function($query) use ($memberId) {
                    $query->where('member_id', $memberId);
                })
                ->where('transaction_type', 'direct_credit')
                ->where('status', 'accepted')
                ->sum('amount');

            $totalWithdrawals = WalletTransaction::whereHas('wallet', function($query) use ($memberId) {
                    $query->where('member_id', $memberId);
                })
                ->where('transaction_type', 'withdrawal')
                ->where('status', 'accepted')
                ->sum('amount');

            $totalPackagePurchases = WalletTransaction::whereHas('wallet', function($query) use ($memberId) {
                    $query->where('member_id', $memberId);
                })
                ->where('transaction_type', 'buy_package')
                ->where('status', 'accepted')
                ->sum('amount');

            // Calculate new balance: Start with direct commissions, then apply internal transfers, then other transactions
            $newBalance = $totalDirectCommissions + $netInternalTransfers + $totalDeposits - $totalWithdrawals - $totalPackagePurchases;

            // Update wallet balance
            $oldBalance = $wallet->balance;
            $wallet->update(['balance' => $newBalance]);

            $this->command->info("Member {$memberId}: Regular wallet balance updated from {$oldBalance} to {$newBalance}");
            $this->command->info("  - Direct commissions: {$totalDirectCommissions}");
            $this->command->info("  - Net internal transfers: {$netInternalTransfers} (sent: -{$totalTransfersSent})");
            $this->command->info("  - Other transactions: " . ($totalDeposits - $totalWithdrawals - $totalPackagePurchases));
            $this->command->info("  ------------------------");
        }
    }

    /**
     * Recalculate all token wallet balances from scratch
     */
    private function recalculateTokenWalletBalances(): void
    {
        $this->command->info("Recalculating token wallet balances...");

        $tokenWallets = TokenWallet::all();

        foreach ($tokenWallets as $tokenWallet) {
            $memberId = $tokenWallet->member_id;

            // Calculate total tokens sent by this member (to subtract)
            $totalTokensSent = TokenTransaction::where('sender_member_id', $memberId)
                ->where('transaction_type', 'send')
                ->where('status', 'sent')
                ->sum('amount');

            // Calculate total tokens received by this member (to add)
            $totalTokensReceived = TokenTransaction::where('receive_member_id', $memberId)
                ->where('transaction_type', 'receive')
                ->where('status', 'received')
                ->sum('amount');

            // Calculate new balance (received - sent)
            $newBalance = $totalTokensReceived - $totalTokensSent;

            // Update token wallet balance
            $oldBalance = $tokenWallet->token_balance;
            $tokenWallet->update(['token_balance' => $newBalance]);

            $this->command->info("Member {$memberId}: Token wallet balance updated from {$oldBalance} to {$newBalance}");
        }
    }
}
