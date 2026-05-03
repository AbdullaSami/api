<?php

namespace Database\Seeders;

use App\Models\TokenWallet;
use App\Models\TokenTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class RollbackTokenTransactionsBeforeDateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Rollback and cancel token wallet transactions made on a specific date.
     */
    public function run(): void
    {
        $targetDate = '2026-05-03'; // Change this to your target date

        $this->command->info("Starting rollback for token transactions on date: {$targetDate}");

        DB::transaction(function () use ($targetDate) {
            // Get all token transactions created on the target date
            $transactions = TokenTransaction::whereDate('created_at', $targetDate)->get();

            if ($transactions->isEmpty()) {
                $this->command->info("No token transactions found for date: {$targetDate}");
                return;
            }

            $this->command->info("Found {$transactions->count()} token transactions to rollback");

            foreach ($transactions as $transaction) {
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
                    $this->command->info("Restored {$transaction->amount} tokens to wallet {$tokenWallet->id} (member {$tokenWallet->member_id}) for sent transaction");
                } elseif ($transaction->transaction_type === 'receive') {
                    // For received transactions, subtract the tokens
                    $tokenWallet->decrement('token_balance', $transaction->amount);
                    $this->command->info("Deducted {$transaction->amount} tokens from wallet {$tokenWallet->id} (member {$tokenWallet->member_id}) for received transaction");
                }

                // Update transaction status to failed/cancelled
                $transaction->update([
                    'status' => 'failed',
                ]);

                $this->command->info("Updated token transaction ID {$transaction->id} status to failed");
            }

            $this->command->info("Successfully rolled back {$transactions->count()} token transactions");
        });

        $this->command->info("Token transaction rollback completed for date: {$targetDate}");
    }
}
