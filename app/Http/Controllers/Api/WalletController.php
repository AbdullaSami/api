<?php

namespace App\Http\Controllers\Api;

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

class WalletController extends Controller
{
    use ApiResponseTrait;


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



    public function myAllTransactions()
    {
        $user = Auth::user();
        $transactions = $user->member->wallet->tarnsactions()->paginate(10);
        $data =  TransactionsResource::collection($transactions)->response()->getData(true);
        if ($transactions) {
            return $this->successResponse('All transactions retrieved successfully', 'transactions', $data);
        }

        return $this->failedResponse();
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
                $wallet->tarnsactions()->create([
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


    // public function internalTransfer(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'receiver_id_code'   => ['nullable', 'numeric'],
    //         'receiver_user_name' => ['required_without:receiver_id_code', 'string'],
    //         'amount'             => ['required', 'numeric', 'min:1']
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->failedResponse($validator->errors(), 422);
    //     }

    //     $user = Auth::user();

    //     $senderMemberId = $user->member->id ?? throw new \Exception('you don\'t have a membership , call you sponser');


    //     DB::beginTransaction();

    //     try {
    //         // Retrieve sender and receiver
    //         $sender = Member::findOrFail($senderMemberId);

    //         $receiverUser = User::when($request->filled('receiver_id_code'), function ($query) use ($request) {
    //             $query->where('id_code', $request->receiver_id_code);
    //         })
    //             ->when($request->filled('receiver_user_name'), function ($query) use ($request) {
    //                 $query->orWhere('username', $request->receiver_user_name);
    //             })
    //             ->first();

    //         if (!$receiverUser) {
    //             throw new \Exception('The receiver is not found.');
    //         }

    //         $receiver = Member::where('user_id', $receiverUser->id)->firstOrFail();

    //         // Verify receiver is part of sender's downline
    //         $downlineIds = $sender->getAllDownlinesNetwork()->pluck('id')->toArray();

    //         if (!in_array($receiver->id, $downlineIds)) {
    //             throw new \Exception('The receiver is not part of the sender’s downline.');
    //         }

    //         // Wallet checks
    //         $senderWallet = $sender->wallet;
    //         $receiverWallet = $receiver->wallet;

    //         if (!$senderWallet || !$receiverWallet) {
    //             throw new \Exception('One or both members do not have a wallet.');
    //         }

    //         if ($senderWallet->balance < $request->amount) {
    //             throw new \Exception('Insufficient balance in sender’s wallet.');
    //         }

    //         // Process transfer
    //         $senderWallet->balance -= $request->amount;
    //         $receiverWallet->balance += $request->amount;

    //         $senderWallet->save();
    //         $receiverWallet->save();

    //         // Log transactions
    //         WalletTransaction::create([
    //             'wallet_id'        => $senderWallet->id,
    //             'transaction_type' => 'send_internal_transfer',
    //             'amount'           => $request->amount,
    //             'status'           => 'accepted',
    //             'receive_member_id' => $receiver->id,
    //         ]);

    //         WalletTransaction::create([
    //             'wallet_id'        => $receiverWallet->id,
    //             'transaction_type' => 'receive_internal_transfer',
    //             'amount'           => $request->amount,
    //             'status'           => 'accepted',
    //             'sender_member_id' => $sender->id,
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'Transfer completed successfully.',
    //         ], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 400);
    //     }
    // }

    public function internalTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id_code'   => ['nullable', 'numeric'],
            'receiver_user_name' => ['required_without:receiver_id_code', 'string'],
            'amount'             => ['required', 'numeric', 'min:1']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $senderMemberId = $user->member->id ?? $this->failedResponse('you don\'t have a membership , call you sponser');

        DB::beginTransaction();

        try {
            // Retrieve sender
            $sender = Member::findOrFail($senderMemberId);

            // Retrieve the receiver using either ID code or username
            $receiverUser = User::when($request->filled('receiver_id_code'), function ($query) use ($request) {
                $query->where('id_code', $request->receiver_id_code);
            })
                ->when($request->filled('receiver_user_name'), function ($query) use ($request) {
                    $query->orWhere('username', $request->receiver_user_name);
                })
                ->first();

            if (!$receiverUser) {
                throw new \Exception('The receiver is not found.');
            }

            // Retrieve receiver's member data
            $receiver = Member::where('user_id', $receiverUser->id)->firstOrFail();

            // Fetch all downlines and uplines for the sender
            $downlineIds = $sender->getAllDownlinesNetwork()->pluck('id')->toArray();
            $uplineIds = collect($sender->getAllUplines())->pluck('id')->toArray();

            // Combine downlines and uplines
            $validIds = array_merge($downlineIds, $uplineIds);

            // Verify receiver is part of the sender's network (downline or upline)
            if (!in_array($receiver->id, $validIds)) {
                throw new \Exception('The receiver is not part of the sender’s network (downline or upline).');
            }

            // Wallet checks
            $senderWallet = $sender->wallet;
            $receiverWallet = $receiver->wallet;

            if (!$senderWallet || !$receiverWallet) {
                throw new \Exception('One or both members do not have a wallet.');
            }

            if ($senderWallet->balance < $request->amount) {
                throw new \Exception('Insufficient balance in sender’s wallet.');
            }

            // Process transfer
            $senderWallet->balance -= $request->amount;
            $receiverWallet->balance += $request->amount;

            $senderWallet->save();
            $receiverWallet->save();

            // Log transactions
            WalletTransaction::create([
                'wallet_id'        => $senderWallet->id,
                'transaction_type' => 'send_internal_transfer',
                'amount'           => $request->amount,
                'status'           => 'accepted',
                'receive_member_id' => $receiver->id,
            ]);

            WalletTransaction::create([
                'wallet_id'        => $receiverWallet->id,
                'transaction_type' => 'receive_internal_transfer',
                'amount'           => $request->amount,
                'status'           => 'accepted',
                'sender_member_id' => $sender->id,
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Transfer completed successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
