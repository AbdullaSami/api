<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function getPaymentHistory()
    {
        $user = auth()->user();

        $paymentHistory = $user->paymentHistories()
            ->orderBy('payment_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $paymentHistory,
        ]);
    }
}
