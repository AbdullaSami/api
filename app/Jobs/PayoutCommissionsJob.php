<?php

namespace App\Jobs;

use App\Models\Commission;
use App\Models\CommissionPayoutBatch;
use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PayoutCommissionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $windowStart = now()->subDays(90);
        $windowEnd = now();

        $batch = CommissionPayoutBatch::create([
            'window_start' => $windowStart,
            'window_end' => $windowEnd,
            'status' => 'processing',
            'total_commissions' => 0,
            'total_amount' => 0,
        ]);

        $sponsorIds = Commission::query()
            ->where('withdrawn', false)
            ->whereBetween('created_at', [$windowStart, $windowEnd])
            ->distinct()
            ->pluck('sponsor_id');

        $totalAmount = 0.0;
        $totalCount = 0;

        try {
            foreach ($sponsorIds as $sponsorId) {
                DB::transaction(function () use ($batch, $sponsorId, $windowStart, $windowEnd, &$totalAmount, &$totalCount) {
                $items = Commission::query()
                    ->where('withdrawn', false)
                    ->where('sponsor_id', $sponsorId)
                    ->whereBetween('created_at', [$windowStart, $windowEnd])
                    ->lockForUpdate()
                    ->get();

                $amount = (float) $items->sum('commission_value');

                if ($amount <= 0 || $items->isEmpty()) {
                    return;
                }

                $wallet = Wallet::firstOrCreate(
                    ['member_id' => $sponsorId],
                    ['balance' => 0]
                );

                $wallet->increment('balance', $amount, []);

                $paidAt = now();

                $wallet->transactions()->create([
                    'transaction_type' => 'commission_payout',
                    'amount' => $amount,
                    'status' => 'accepted',
                    'sender_member_id' => null,
                    'receive_member_id' => $sponsorId,
                    'package_name' => 'commission_payout',
                    'payout_batch_id' => $batch->id,
                ]);

                Commission::query()
                    ->whereIn('id', $items->pluck('id')->all())
                    ->update([
                        'withdrawn' => true,
                        'withdrawn_at' => $paidAt,
                        'payout_batch_id' => $batch->id,
                    ]);

                $totalAmount += $amount;
                $totalCount += $items->count();
            });
            }

            $batch->update([
                'status' => 'completed',
                'total_commissions' => $totalCount,
                'total_amount' => $totalAmount,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'meta' => ['error' => $e->getMessage()],
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }
}
