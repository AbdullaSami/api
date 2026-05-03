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

                    // Reverse the wallet balance based on transaction type
                    switch ($transaction->transaction_type) {
                        case 'withdrawal':
                        case 'send_internal_transfer':
                        case 'buy_package':
                            // For these transactions, add back the amount
                            $wallet->increment('balance', $transaction->amount);
                            $this->command->info("Restored {$transaction->amount} to wallet {$wallet->id} (member {$wallet->member_id}) for {$transaction->transaction_type}");
                            break;
                            
                        case 'deposit':
                        case 'receive_internal_transfer':
                        case 'direct_credit':
                            // For these transactions, subtract the amount
                            $wallet->decrement('balance', $transaction->amount);
                            $this->command->info("Deducted {$transaction->amount} from wallet {$wallet->id} (member {$wallet->member_id}) for {$transaction->transaction_type}");
                            break;
                    }

                    // Update transaction status to rejected
                    $transaction->update([
                        'status' => 'rejected',
                    ]);

                    $this->command->info("Updated wallet transaction ID {$transaction->id} status to rejected");
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

                    // Reverse the token balance based on transaction type
                    if ($transaction->transaction_type === 'send') {
                        // For sent transactions, add back the tokens
                        $tokenWallet->increment('token_balance', $transaction->amount);
                        $this->command->info("Restored {$transaction->amount} tokens to token wallet {$tokenWallet->id} (member {$tokenWallet->member_id}) for sent transaction");
                    } elseif ($transaction->transaction_type === 'receive') {
                        // For received transactions, subtract the tokens
                        $tokenWallet->decrement('token_balance', $transaction->amount);
                        $this->command->info("Deducted {$transaction->amount} tokens from token wallet {$tokenWallet->id} (member {$tokenWallet->member_id}) for received transaction");
                    }

                    // Update transaction status to failed
                    $transaction->update([
                        'status' => 'failed',
                    ]);

                    $this->command->info("Updated token transaction ID {$transaction->id} status to failed");
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
            
            // Calculate total transfers received
            $totalTransfersReceived = WalletTransaction::where('receive_member_id', $memberId)
                ->where('transaction_type', 'receive_internal_transfer')
                ->where('status', 'accepted')
                ->sum('amount');
            
            // Calculate total transfers sent - to subtract
            $totalTransfersSent = WalletTransaction::where('sender_member_id', $memberId)
                ->where('transaction_type', 'send_internal_transfer')
                ->where('status', 'accepted')
                ->sum('amount');
            
            // Calculate total deposits
            $totalDeposits = WalletTransaction::whereHas('wallet', function($query) use ($memberId) {
                    $query->where('member_id', $memberId);
                })
                ->where('transaction_type', 'direct_credit')
                ->where('status', 'accepted')
                ->sum('amount');
            
            // Calculate total withdrawals - to subtract
            $totalWithdrawals = WalletTransaction::whereHas('wallet', function($query) use ($memberId) {
                    $query->where('member_id', $memberId);
                })
                ->where('transaction_type', 'withdrawal')
                ->where('status', 'accepted')
                ->sum('amount');
            
            // Calculate total package purchases - to subtract
            $totalPackagePurchases = WalletTransaction::whereHas('wallet', function($query) use ($memberId) {
                    $query->where('member_id', $memberId);
                })
                ->where('transaction_type', 'buy_package')
                ->where('status', 'accepted')
                ->sum('amount');
            
            // Calculate new balance
            $newBalance = $totalDirectCommissions 
                + $totalTransfersReceived 
                + $totalDeposits
                - $totalTransfersSent
                - $totalWithdrawals
                - $totalPackagePurchases;
            
            // Update wallet balance
            $oldBalance = $wallet->balance;
            $wallet->update(['balance' => $newBalance]);
            
            $this->command->info("Member {$memberId}: Regular wallet balance updated from {$oldBalance} to {$newBalance}");
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
