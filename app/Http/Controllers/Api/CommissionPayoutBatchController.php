<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\CommissionPayoutBatch;
use App\Models\WalletTransaction;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommissionPayoutBatchController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $user = Auth::user();
        $member = $user?->member;

        if (!$member) {
            return $this->successResponse('batches fetched successfully', 'batches', []);
        }

        $batches = CommissionPayoutBatch::query()
            ->whereHas('commissions', function ($q) use ($member) {
                $q->where('sponsor_id', $member->id);
            })
            ->withCount([
                'commissions as my_commissions_count' => function ($q) use ($member) {
                    $q->where('sponsor_id', $member->id);
                },
            ])
            ->withSum([
                'commissions as my_total_amount' => function ($q) use ($member) {
                    $q->where('sponsor_id', $member->id);
                },
            ], 'commission_value')
            ->orderByDesc('id')
            ->paginate(10);

        return $this->successResponse('batches fetched successfully', 'batches', $batches);
    }

    public function show(Request $request, int $id)
    {
        $user = Auth::user();
        $member = $user?->member;

        if (!$member) {
            return $this->failedResponse('member not found', 404);
        }

        $batch = CommissionPayoutBatch::query()
            ->where('id', $id)
            ->whereHas('commissions', function ($q) use ($member) {
                $q->where('sponsor_id', $member->id);
            })
            ->first();

        if (!$batch) {
            return $this->failedResponse('batch not found', 404);
        }

        $commissions = Commission::query()
            ->where('commissions.payout_batch_id', $batch->id)
            ->where('commissions.sponsor_id', $member->id)
            ->leftJoin('members as r', 'commissions.referral_id', '=', 'r.id')
            ->leftJoin('users as ru', 'r.user_id', '=', 'ru.id')
            ->select([
                'commissions.*',
                'ru.username as referral_username',
                'ru.id_code as referral_id_code',
            ])
            ->orderByDesc('commissions.id')
            ->paginate(20);

        $walletTransaction = WalletTransaction::query()
            ->where('payout_batch_id', $batch->id)
            ->where('transaction_type', 'commission_payout')
            ->where('receive_member_id', $member->id)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'status' => true,
            'message' => 'batch fetched successfully',
            'batch' => $batch,
            'commissions' => $commissions,
            'wallet_transaction' => $walletTransaction,
        ]);
    }
}
