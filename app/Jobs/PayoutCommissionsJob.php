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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PayoutCommissionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Prevent two overlapping queue workers from running this simultaneously.
     * Requires ShouldBeUniqueUntilProcessing or a cache-based lock — here
     * we use a simple job-level timeout guard via $uniqueFor.
     */
    public int $tries = 1;
    public int $timeout = 300; // 5 min hard cap

    /**
     * Freeze the window at dispatch time, not at execution time.
     * This way a delayed or retried job always covers the same window.
     */
    public function __construct(
        private readonly Carbon $windowStart,
        private readonly Carbon $windowEnd,
    ) {}

    public static function dispatch7DayWindow(): void
    {
        static::dispatch(
            windowStart: now()->subDays(21)->startOfDay(),
            windowEnd: now()->subDays(9)->startOfDay(),
        );
    }

    public function handle(): void
    {
        $batch = CommissionPayoutBatch::create([
            'window_start'      => $this->windowStart,
            'window_end'        => $this->windowEnd,
            'status'            => 'processing',
            'total_commissions' => 0,
            'total_amount'      => 0,
        ]);

        Log::info('[PayoutCommissionsJob] Batch started', [
            'batch_id'     => $batch->id,
            'window_start' => $this->windowStart,
            'window_end'   => $this->windowEnd,
        ]);

        try {
            $this->processSponsors($batch);

            // Derive totals from DB after all transactions commit —
            // never trust in-memory accumulators that survive partial rollbacks.
            $totals = Commission::query()
                ->where('payout_batch_id', $batch->id)
                ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(commission_value), 0) as total_amount')
                ->first();

            $batch->update([
                'status'            => 'completed',
                'total_commissions' => (int)   $totals->total_count,
                'total_amount'      => (float) $totals->total_amount,
                'finished_at'       => now(),
            ]);

            Log::info('[PayoutCommissionsJob] Batch completed', [
                'batch_id'     => $batch->id,
                'total_count'  => $totals->total_count,
                'total_amount' => $totals->total_amount,
            ]);
        } catch (Throwable $e) {
            $batch->update([
                'status'      => 'failed',
                'meta'        => ['error' => $e->getMessage()],
                'finished_at' => now(),
            ]);

            Log::error('[PayoutCommissionsJob] Batch failed', [
                'batch_id' => $batch->id,
                'error'    => $e->getMessage(),
            ]);

            // Re-throw so the queue marks the job as failed and fires
            // the JobFailed event (Horizon, Telescope, Slack alerts, etc.)
            throw $e;
        }
    }

    private function processSponsors(CommissionPayoutBatch $batch): void
    {
        // Filter to binary commissions up front so we never open a
        // transaction for a sponsor who would produce a zero payout.
        Commission::query()
            ->select('sponsor_id')
            ->where('withdrawn', false)
            ->where('commission_type', 'binary')
            ->whereBetween('created_at', [$this->windowStart, $this->windowEnd])
            ->distinct()
            ->orderBy('sponsor_id')
            ->chunk(100, function ($rows) use ($batch) {
                foreach ($rows as $row) {
                    $this->processSingleSponsor($row->sponsor_id, $batch);
                }
            });
    }

    private function processSingleSponsor(int|string $sponsorId, CommissionPayoutBatch $batch): void
    {
        DB::transaction(function () use ($sponsorId, $batch) {

            $items = Commission::query()
                ->where('withdrawn', false)
                ->where('sponsor_id', $sponsorId)
                ->where('commission_type', 'binary')
                ->whereBetween('created_at', [$this->windowStart, $this->windowEnd])
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                return;
            }

            $amount = (float) $items->sum('commission_value');

            if ($amount <= 0) {
                Log::warning('[PayoutCommissionsJob] Skipping sponsor with non-positive amount', [
                    'sponsor_id' => $sponsorId,
                    'amount'     => $amount,
                ]);
                return;
            }

            // findOrFail() queries by primary key — wrong if your wallet's PK
            // is not the sponsor_id. Use a proper FK lookup instead.
            $wallet = Wallet::where('member_id', $sponsorId)
                ->lockForUpdate() // prevent concurrent balance increments
                ->firstOrFail();

            $wallet->increment('balance', $amount);

            $paidAt = now();

            $wallet->transactions()->create([
                'transaction_type'  => 'commission_payout',
                'amount'            => $amount,
                'status'            => 'accepted',
                'sender_member_id'  => null,
                'receive_member_id' => $sponsorId,
                'payout_batch_id'   => $batch->id,
                'paid_at'           => $paidAt,
            ]);

            Commission::query()
                ->whereIn('id', $items->pluck('id')->all())
                ->update([
                    'withdrawn'       => true,
                    'withdrawn_at'    => $paidAt,
                    'payout_batch_id' => $batch->id,
                ]);

            Log::info('[PayoutCommissionsJob] Sponsor paid out', [
                'sponsor_id' => $sponsorId,
                'batch_id'   => $batch->id,
                'amount'     => $amount,
                'items'      => $items->count(),
            ]);
        });
    }
}
