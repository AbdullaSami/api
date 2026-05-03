<?php

namespace Database\Seeders;

use App\Models\Commission;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class RecalculateWalletBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Recalculates wallet balances based on direct commissions and transfers.
     */
    public function run(): void
    {
        $this->command->info("Starting wallet balance recalculation...");

        DB::transaction(function () {
            // Get all wallets
            $wallets = Wallet::all();
            
            foreach ($wallets as $wallet) {
                $memberId = $wallet->member_id;
                
                // Calculate total direct commissions for this member
                $totalDirectCommissions = Commission::where('sponsor_id', $memberId)
                    ->where('commission_type', 'direct')
                    ->sum('commission_value');
                
                // Calculate total transfers received (receive_internal_transfer)
                $totalTransfersReceived = WalletTransaction::where('receive_member_id', $memberId)
                    ->where('transaction_type', 'receive_internal_transfer')
                    ->where('status', 'accepted')
                    ->sum('amount');
                
                // Calculate total transfers sent (send_internal_transfer) - to subtract
                $totalTransfersSent = WalletTransaction::where('sender_member_id', $memberId)
                    ->where('transaction_type', 'send_internal_transfer')
                    ->where('status', 'accepted')
                    ->sum('amount');
                
                // Calculate total deposits (direct_credit)
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
                
                $this->command->info("Member {$memberId}: Balance updated from {$oldBalance} to {$newBalance}");
                $this->command->info("  - Direct commissions: {$totalDirectCommissions}");
                $this->command->info("  - Transfers received: {$totalTransfersReceived}");
                $this->command->info("  - Transfers sent: -{$totalTransfersSent}");
                $this->command->info("  - Deposits: {$totalDeposits}");
                $this->command->info("  - Withdrawals: -{$totalWithdrawals}");
                $this->command->info("  - Package purchases: -{$totalPackagePurchases}");
                $this->command->info("  ------------------------");
            }
        });

        $this->command->info("Wallet balance recalculation completed.");
    }
}
