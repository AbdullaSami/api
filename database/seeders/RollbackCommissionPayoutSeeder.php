<?php

namespace Database\Seeders;

use App\Models\Commission;
use App\Models\CommissionPayoutBatch;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class RollbackCommissionPayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Rollback commission payout for a specific batch_id.
     */
    public function run(): void
    {
        $batchId = 2; // Change this to the batch_id you want to rollback

        $batch = CommissionPayoutBatch::find($batchId);

        if (!$batch) {
            $this->command->error("Commission payout batch with ID {$batchId} not found.");
            return;
        }

        $this->command->info("Starting rollback for batch {$batchId}...");

        DB::transaction(function () use ($batch) {
            // Get all commissions for this batch with commission_type = "binary"
            $commissions = Commission::where('payout_batch_id', $batch->id)
                ->where('commission_type', 'binary')
                ->get();

            // Group commissions by sponsor_id to calculate total binary amount per sponsor
            $commissionsBySponsor = $commissions->groupBy('sponsor_id');

            foreach ($commissionsBySponsor as $sponsorId => $sponsorCommissions) {
                // Calculate total binary commission amount for this sponsor
                $totalBinaryAmount = $sponsorCommissions->sum('commission_value');

                // Reverse the wallet balance for this sponsor
                $wallet = Wallet::where('member_id', $sponsorId)->first();
                if ($wallet) {
                    $wallet->decrement('balance', $totalBinaryAmount);
                    $this->command->info("Decremented wallet balance for member {$sponsorId} by {$totalBinaryAmount}");
                }

                // Reset each binary commission status
                foreach ($sponsorCommissions as $commission) {
                    $commission->update([
                        'withdrawn' => false,
                        'withdrawn_at' => null,
                        'payout_batch_id' => null,
                    ]);

                    $this->command->info("Reset binary commission ID {$commission->id} for sponsor {$sponsorId}");
                }
            }

            // Update batch status
            $batch->update([
                'status' => 'failed',
                'finished_at' => now(),
                'meta' => array_merge($batch->meta ?? [], [
                    'cancelled_at' => now()->toDateTimeString(),
                    'reason' => 'Manual rollback via seeder',
                ]),
            ]);

            $this->command->info("Updated batch {$batch->id} status to failed");
        });

        $this->command->info("Rollback completed for batch {$batchId}.");
    }
}
