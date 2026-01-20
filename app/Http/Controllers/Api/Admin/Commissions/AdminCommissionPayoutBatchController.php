<?php

namespace App\Http\Controllers\Api\Admin\Commissions;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\CommissionPayoutBatch;
use App\Models\WalletTransaction;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class AdminCommissionPayoutBatchController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $batches = CommissionPayoutBatch::query()
            ->orderByDesc('id')
            ->paginate(20);

        return $this->successResponse('batches fetched successfully', 'batches', $batches);
    }

    public function show(Request $request, int $id)
    {
        $batch = CommissionPayoutBatch::find($id, ['*']);

        if (!$batch) {
            return $this->failedResponse('batch not found', 404);
        }

        $recipients = Commission::query()
            ->where('commissions.payout_batch_id', $batch->id)
            ->leftJoin('members as m', 'commissions.sponsor_id', '=', 'm.id')
            ->leftJoin('users as u', 'm.user_id', '=', 'u.id')
            ->selectRaw('commissions.sponsor_id as member_id, u.username, u.id_code, COUNT(*) as commissions_count, SUM(commissions.commission_value) as total_amount')
            ->groupBy('commissions.sponsor_id', 'u.username', 'u.id_code')
            ->orderByDesc('total_amount')
            ->paginate(20);

        $walletTransactions = WalletTransaction::query()
            ->where('payout_batch_id', $batch->id)
            ->where('transaction_type', 'commission_payout')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'message' => 'batch fetched successfully',
            'batch' => $batch,
            'recipients' => $recipients,
            'wallet_transactions' => $walletTransactions,
        ]);
    }
}
