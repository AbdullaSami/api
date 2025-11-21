<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PinCheckerInterface;
use App\Models\User;
use App\Models\Member;
use App\Models\CreditCodes;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\TransactionsResource;
use App\Http\Requests\StoreWalletTransaction;

use function Symfony\Component\String\u;

class WalletController extends Controller
{
    use ApiResponseTrait;

    protected $pins;

    public function __construct(PinCheckerInterface $pins)
    {
        $this->pins = $pins;
    }


    public function myCurrentBalance()
    {
        $user = Auth::user();
        $member = $user->member;
        $balance = $member->wallet->balance;
        return response()->json([
            'status' => true,
            'balance' => $balance
        ]);
    }

    /**
     * calculate total earnings from commissions
     * abdulla sami 2024-17-NOV
     */

    public function getTotals()
    {
        $user = Auth::user();
        $member = $user->member;

        // 1 - Earnings (You must decide the correct model/table)
        $totalEarnings = $member->wallet
            ->transactions()
            ->sum('amount');

        // 2 - Received internal transfer
        $totalReceive = $member->wallet->transactions()
            ->where('transaction_type', 'receive_internal_transfer')
            ->where('status', 'accepted')
            ->sum('amount');

        // 3 - Sent transfer
        $totalTransfer = $member->wallet->transactions()
            ->where('transaction_type', 'send_internal_transfer')
            ->where('status', 'accepted')
            ->sum('amount');

        // 4 - Deposit
        $totalBounce = $member->wallet->transactions()
            ->where('transaction_type', 'deposit')
            ->where('status', 'accepted')
            ->sum('amount');

        return response()->json([
            'status' => true,
            'total_earnings' => $totalEarnings,
            'total_receive' => $totalReceive,
            'total_transfer' => $totalTransfer,
            'total_bounce' => $totalBounce
        ]);
    }

    /**
     * Get reports data
     * abdulla sami 2024-17-NOV
     */

    public function getReportsData()
    {
        $user = Auth::user();
        $member = $user->member;
        $wallet = $member->wallet;

        $currentYear = now()->year;

        // Weekly Earnings
        $weeklyEarnings = $wallet->transactions()
            ->whereYear('created_at', $currentYear)
            ->where('transaction_type', 'earning') // change if needed
            ->where('status', 'accepted')
            ->selectRaw('WEEK(created_at) as week, SUM(amount) as total')
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        // Generate full 52-week report
        $fullWeeklyEarnings = collect(range(1, 52))->map(function ($week) use ($currentYear, $weeklyEarnings) {
            $weekData = $weeklyEarnings->firstWhere('week', $week);
            return [
                'week' => $week,
                'total' => $weekData ? $weekData->total : 0,
            ];
        });

        // Raw Monthly bounce from DB
        $rawBounce = $wallet->transactions()
            ->whereYear('created_at', $currentYear)
            ->where('transaction_type', 'deposit')
            ->where('status', 'accepted')
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month'); // returns: [3 => 200, 7 => 500]

        // Generate Full 12-Month Report with Names
        $months = collect(range(1, 12))->map(function ($monthNumber) use ($rawBounce) {
            return [
                'month' => \Carbon\Carbon::create()->month($monthNumber)->format('F'),
                'total' => $rawBounce[$monthNumber] ?? 0,
            ];
        });

        return response()->json([
            'status'          => true,
            'weekly_earnings' => $fullWeeklyEarnings,
            'monthly_bounce'  => $months,
        ]);
    }



    public function myAllTransactions()
    {
        try {
            $user = Auth::user();
            $transactions = $user->member->wallet->transactions()->paginate(10);
            $data =  TransactionsResource::collection($transactions)->response()->getData(true);
            $tokenTransactions = $user->member->tokenWallet->transaction()->paginate(10);
            $tokenData = TransactionsResource::collection($tokenTransactions)->response()->getData(true);

            return response()->json([
                'data' => $data,
                'token' => $tokenData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function myAcceptedTransactions()
    {
        $user = Auth::user();
        $wallet_id =  $user->member->wallet->id;

        $transactions = WalletTransaction::where('wallet_id', $wallet_id)->where('status', 'accepted')->paginate(10);
        TransactionsResource::collection($transactions)->response()->getData(true);
        if ($transactions)
            return $this->successResponse('All accepted transactions fetched successfully', 'transactions', $transactions);

        return $this->failedResponse();
    }



    public function myRejectedTransactions()
    {
        $user = Auth::user();
        $wallet_id =  $user->member->wallet->id;

        $transactions = WalletTransaction::where('wallet_id', $wallet_id)->where('status', 'rejected')->paginate(10);
        TransactionsResource::collection($transactions)->response()->getData(true);
        if ($transactions)
            return $this->successResponse('All rejected transactions fetched successfully', 'transactions', $transactions);

        return $this->failedResponse();
    }


    public function myPendingTransactions()
    {
        $user = Auth::user();
        $wallet_id =  $user->member->wallet->id;

        $transactions = WalletTransaction::where('wallet_id', $wallet_id)->where('status', 'pending')->paginate(10);
        TransactionsResource::collection($transactions)->response()->getData(true);

        if ($transactions)
            return $this->successResponse('All rejected transactions fetched successfully', 'transactions', $transactions);

        return $this->failedResponse();
    }


    public function myWithdrawalTransactions()
    {
        $user = Auth::user();
        $wallet_id =  $user->member->wallet->id;

        $transactions = WalletTransaction::where('wallet_id', $wallet_id)->where('transaction_type', 'withdrawal')->paginate(10);
        TransactionsResource::collection($transactions)->response()->getData(true);
        if ($transactions)
            return $this->successResponse('All withdrawal transactions fetched successfully', 'transactions', $transactions);
        return $this->failedResponse();
    }

    public function myDepositTransactions()
    {
        $user = Auth::user();
        $wallet_id =  $user->member->wallet->id;
        $transactions = WalletTransaction::where('wallet_id', $wallet_id)->where('transaction_type', 'deposit')->paginate(10);
        TransactionsResource::collection($transactions)->response()->getData(true);
        if ($transactions)
            return $this->successResponse('All deposit transactions fetched successfully', 'transactions', $transactions);

        return $this->failedResponse();
    }


    public function withdrawal(StoreWalletTransaction $request)
    {
        $user = Auth::user();
        $wallet = $user->member->wallet;
        if ($wallet->balance < $request->amount)
            return $this->failedResponse('Insufficient balance , whice your balance is :: ' . $wallet->balance . " USD");

        $transaction = WalletTransaction::create([
            'wallet_id'         => $wallet->id,
            'transaction_type'  => $request->transaction_type,
            'amount'            => $request->amount
        ]);
        $transaction =  new TransactionsResource($transaction);
        // handel notification to the admin to approve or reject the request |
        if ($transaction)
            return $this->successResponse('your request has been sent succcessfully', 'transaction', $transaction);
        return $this->failedResponse();
    }


    public function chargingCredit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'numeric', 'exists:credit_codes,code', 'digits:14']
        ]);
        if ($validator->fails())
            return $this->failedResponse($validator->errors(), 422);
        $code = CreditCodes::where('code', $request->code)->first();
        $user = auth()->user();
        $wallet = $user->member->wallet;
        if ($code->status == 'inactive' && $code->charged_by == $user->id)
            return $this->failedResponse('This card has been charged to this account before');
        if ($code->status == 'inactive' && $code->charged_by !== $user->id)
            return $this->failedResponse('This card has been charged to a different account before');
        try {

            if ($code->status == 'active' && $code->charged_by == null) {
                DB::beginTransaction();
                $code->update([
                    'status' => 'inactive',
                    'charged_by' => $user->id
                ]);
                $wallet->update([
                    'balance' => $wallet->balance + $code->credit
                ]);
                $wallet->transactions()->create([
                    'transaction_type' => 'direct_credit',
                    'amount' => $code->credit,
                    'status' => 'accepted',
                    'credit_code' => $code->code,
                ]);
                DB::commit();
                return $this->successResponse('The card has been charged and the balance has been successfully added to your wallet', 'user', $user->load('member.wallet'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
    }


    /**
     * Create transfer to token wallet from commission wallet
     * abdulla sami 2024-18-NOV
     */

    public function transferToTokenWallet(Request $request)
    {

        $request->validate([
            'amount' => ['required', 'numeric'],
            'pin_code' => ['required', 'string'],
        ]);
        $user = Auth::user();
        $member = $user->member;
        $wallet = $member->wallet;
        $tokenWallet = $member->tokenWallet;

        if ($request->input('amount') <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Transfer amount must be greater than zero.'
            ], 400);
        }

        $amount = $request->input('amount');

        if ($wallet->balance < $amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance in commission wallet.'
            ], 400);
        }

        // verify PIN code
        $result = $this->pins->check($user, $request->input('pin_code'));

        // Handle various error reasons
        if ($result['reason'] == 'invalid') {
            return response()->json([
                'status' => false,
                'message' => 'Invalid PIN code.'
            ], 400);
        } elseif ($result['reason'] == 'locked') {
            return response()->json([
                'status' => false,
                'message' => 'PIN code is locked. Try again after ' . $result['locked_until']
            ], 403);
        } elseif ($result['reason'] == 'no_pin_set') {
            return response()->json([
                'status' => false,
                'message' => 'No PIN code set for this user.'
            ], 400);
        }
        DB::beginTransaction();
        try {
            // Deduct from commission wallet
            $wallet->decrement('balance', $amount);
            $wallet->transactions()->create([
                'transaction_type' => 'send_internal_transfer',
                'amount' => $amount,
                'status' => 'accepted',
                'receive_member_id' => $member->id,
            ]);

            // Add to token wallet
            $tokenWallet->increment('token_balance', $amount);
            $tokenWallet->transaction()->create([
                'transaction_type' => 'receive',
                'status' => 'received',
                'amount' => $amount,
                'sender_member_id' => $member->id,
                'receive_member_id' => $member->id,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Transfer to token wallet successful.',
                'commission_wallet_balance' => $wallet->balance,
                'token_wallet_balance' => $tokenWallet->token_balance
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Transfer failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function tokenWallet(Request $request)
    {
        $user = Auth::user();
        $member = $user->member;
        $tokenWallet = $member->tokenWallet;

        return response()->json([
            'status' => true,
            'token_wallet_balance' => $tokenWallet->token_balance
        ]);
    }

    public function internalTransfer(Request $request)
    {
        $request->validate([
            'recipient_member_code' => ['required', 'string', 'exists:users,id_code'],
            'amount' => ['required', 'numeric', 'min:1'],
            'pin_code' => ['required', 'string'],
        ]);
        $user = Auth::user();
        $member = $user->member;
        $wallet = $member->tokenWallet;
        $recipientMember = User::where('id_code', $request->input('recipient_member_code'))->first();
        $recipientWallet = $recipientMember->member->tokenWallet;

        if (!$recipientMember) {
            return response()->json([
                'status' => false,
                'message' => 'Recipient member not found.'
            ], 404);
        }

        if ($wallet->token_balance < $request->input('amount')) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance for transfer.'
            ], 400);
        }

        // verify PIN code
        $result = $this->pins->check($user, $request->input('pin_code'));
        // Handle various error reasons
        if ($result['reason'] == 'invalid') {
            return response()->json([
                'status' => false,
                'message' => 'Invalid PIN code.'
            ], 400);
        } elseif ($result['reason'] == 'locked') {
            return response()->json([
                'status' => false,
                'message' => 'PIN code is locked. Try again after ' . $result['locked_until']
            ], 403);
        } elseif ($result['reason'] == 'no_pin_set') {
            return response()->json([
                'status' => false,
                'message' => 'No PIN code set for this user.'
            ], 400);
        }
        DB::beginTransaction();
        try {
            // Deduct from sender's wallet
            $wallet->decrement('token_balance', $request->input('amount'));
            $wallet->transaction()->create([
                'transaction_type' => 'send',
                'amount' => $request->input('amount'),
                'status' => 'sent',
                'receive_member_id' => $recipientMember->member->id,
                'sender_member_id' => $member->id,

            ]);

            // Add to recipient's wallet
            $recipientWallet->increment('token_balance', $request->input('amount'));
            $recipientWallet->transaction()->create([
                'transaction_type' => 'receive',
                'amount' => $request->input('amount'),
                'status' => 'received',
                'receive_member_id' => $recipientMember->member->id,
                'sender_member_id' => $member->id,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Internal transfer successful.',
                'sender_wallet_balance' => $wallet->token_balance
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Transfer failed: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Create Token Wallet
     * abdulla sami 2024-18-NOV
     */

    public function createTokenWallet($id)
    {
        $user = User::findOrFail($id);
        $member = $user->member;

        // Create a new token wallet
        $tokenWallet = $member->tokenWallet()->create([
            'token_balance' => 0.00,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Token wallet created successfully.',
            'token_wallet' => $tokenWallet
        ]);
    }
}
