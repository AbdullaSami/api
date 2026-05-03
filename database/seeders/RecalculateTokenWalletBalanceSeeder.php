<?php

namespace Database\Seeders;

use App\Models\TokenWallet;
use App\Models\TokenTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class RecalculateTokenWalletBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Recalculates token wallet balances based on all token transactions.
     */
    public function run(): void
    {
        $this->command->info("Starting token wallet balance recalculation...");

        DB::transaction(function () {
            // Get all token wallets
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
                
                $this->command->info("Member {$memberId}: Token balance updated from {$oldBalance} to {$newBalance}");
                $this->command->info("  - Tokens received: {$totalTokensReceived}");
                $this->command->info("  - Tokens sent: -{$totalTokensSent}");
                $this->command->info("  ------------------------");
            }
        });

        $this->command->info("Token wallet balance recalculation completed.");
    }
}
