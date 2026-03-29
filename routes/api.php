<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MLMController;
use App\Http\Controllers\Api\RankController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\NodeValidationController;
use App\Http\Controllers\Api\Auth\Users\AuthController;
use App\Http\Controllers\Api\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Api\Admin\Users\AdminUserController;
use App\Http\Controllers\Api\Auth\Users\ResetPasswordController;
use App\Http\Controllers\Api\Admin\Credits\AdminCreditController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\CommissionPayoutBatchController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\TicketsController;
use App\Http\Controllers\Api\Admin\Commissions\AdminCommissionPayoutBatchController;
use App\Http\Controllers\Api\AnalyticsController;

route::any('login', function () {
    return response()->json('you are unauthorized', 400);
})->name('login');

// CORS Test Route - Returns user info with authentication methods
Route::get('v1/user', function () {
    $user = auth('sanctum')->user();

    if (!$user) {
        return response()->json([
            'message' => 'Unauthenticated',
            'auth_method' => 'none',
            'cors_test' => true
        ], 401);
    }

    return response()->json([
        'user' => $user->only(['id', 'email', 'name']),
        'auth_method' => request()->bearerToken() ? 'bearer_token' : 'cookie',
        'cors_test' => true,
        'timestamp' => now()->toISOString()
    ]);
});

route::prefix('v1')->group(function () {

    route::post('login', [AuthController::class, 'login']);
    route::post('register', [AuthController::class, 'register']);
    route::get('sponsor-data/{id}', [AuthController::class, 'sponsorData']);

    // single sign-On
    Route::post('login-token', [AuthController::class, 'generateToken']);

    //ranks
    route::get('ranks', [RankController::class, 'ranks']);
    // packages
    Route::get('packages', [PackageController::class, 'index']);
    
    route::middleware('auth:sanctum')->group(function () {
        // logout
        route::post('logout', [AuthController::class, 'logout']);

        // user profile data
        route::get('user/data', [AuthController::class, 'userProfile']);
        route::get('user/data/{id_code}', [AuthController::class, 'profileById']); // Abdulla Sami 2025-25-NOV
        route::post('user/delete', [AuthController::class, 'deleteMyUser']);
        route::post('user/active', [AuthController::class, 'activeUser']);
        route::post('user/inactive', [AuthController::class, 'inactiveUser']);
        route::post('user/change-profile-image', [AuthController::class, 'changeProfileImage']);

        // OTP-protected update routes (rate limited via config)
        Route::middleware('throttle:' . config('security.otp_rate_limit', '3,1'))->group(function () {
            Route::post('user/profile/request-update', [AuthController::class, 'requestProfileUpdate']);
            Route::post('user/password/request-change', [AuthController::class, 'requestPasswordChange']);
            Route::post('user/pin/request-change', [AuthController::class, 'requestPinChange']);
        });

        // OTP verification and apply update
        Route::post('user/verify-otp-and-update', [AuthController::class, 'verifyOtpAndApplyUpdate']);

        // single sign-On
        Route::get('get-login-user', [AuthController::class, 'getUser']);

        // user reset password
        Route::post('user/password/email', [ResetPasswordController::class, 'sendResetLinkEmail']);
        Route::patch('user/password/reset', [ResetPasswordController::class, 'reset']);

        // user reset pin (legacy - kept for backward compatibility)
        Route::post('user/pin/reset', [AuthController::class, 'resetPin']);

        //tank
        route::get('user-tank', [MLMController::class, 'mtTank']);

        // members
        Route::post('place-referral', [MLMController::class, 'placeReferral']);
        route::get('all-downline-members', [MLMController::class, 'getDownlineMembers']);
        route::get('direct-downline-members', [MLMController::class, 'getDirectDownlineMembers']);
        route::get('get-direct-downline-members/{id}', [MLMController::class, 'getDirectDownlineMembersById']);
        route::get('left-downline-members', [MLMController::class, 'getLeftDownlineMembers']);
        route::get('right-downline-members', [MLMController::class, 'getRightDownlineMembers']);
        Route::get('/members/downlines', [MLMController::class, 'getDownlineDetails']);

        Route::get('/members/yearly-sales', [MLMController::class, 'getYearlySales']);

        //packeage
        route::get('my-package', [PackageController::class, 'show']);

        //subscription
        Route::post('subscribe', [SubscriptionController::class, 'store']);

        //wallet
        route::get('current-balance', [WalletController::class, 'myCurrentBalance']);
        route::get('all-tarnsactions', [WalletController::class, 'myAllTransactions']);
        route::get('all-accetptd-tarnsactions', [WalletController::class, 'myAcceptedTransactions']);
        route::get('all-rejected-tarnsactions', [WalletController::class, 'myRejectedTransactions']);
        route::get('all-pending-tarnsactions', [WalletController::class, 'myPendingTransactions']);
        route::get('all-withdrawal-tarnsactions', [WalletController::class, 'myWithdrawalTransactions']);
        route::get('all-deposit-tarnsactions', [WalletController::class, 'myDepositTransactions']);
        route::post('withdrawal', [WalletController::class, 'withdrawal']);
        route::post('charging-credit', [WalletController::class, 'chargingCredit']);

        //abdulla sami 2025-17-NOV
        route::get('wallet-totals', [WalletController::class, 'getTotals']);
        route::get('wallet-reports', [WalletController::class, 'getReportsData']);
        //abdulla sami 2025-18-NOV
        route::post('transfer-to-token-wallet', [WalletController::class, 'transferToTokenWallet']);
        //abdulla sami 2025-19-NOV
        route::get('token-wallet-balance', [WalletController::class, 'tokenWallet']);
        route::post('internal-transfer', [WalletController::class, 'internalTransfer']);
        //abdulla sami 2025-24-NOV
        route::get('commission-summary', [CommissionController::class, 'index']);
        route::get('commission-payout-batches', [CommissionPayoutBatchController::class, 'index']); //new
        route::get('commission-payout-batches/{id}', [CommissionPayoutBatchController::class, 'show']); //new
        //abdulla sami 2025-1-DEC
        route::get('yearly-sales-in-Weeks', [WalletController::class, 'dashboardReports']);
        route::get('user-tickets', [TicketsController::class, 'showUserTickets']);
        route::post('create-ticket', [TicketsController::class, 'store']);
        //abdulla sami 2025-2-DEC
        route::get('total-down-line', [MLMController::class, 'getDownlineCounts']);
        route::get('network-volume', [MLMController::class, 'getNetworkVolume']);
        //abdulla sami 2025-3-DEC
        route::get('my-ranks', [RankController::class, 'rankHistory']);
        /**
         * abdulla's personal notes:
         * 1 - create checkPassword service to verify user password before internal transfer
         * */

        /**
         * ===================================================================================================
         * Start new routes for new website 2026-18-March
         * 1- members/downlines #done
         * 2- total-down-line #done
         * 3- network-volume #done
         * 4-rank
         * 5-yearly-sales-in-Weeks
         *
         * Wallet data and reports
         * 1- wallet-totals #done
         * 2- wallet-reports #done
         * 3- current-balance #done
         * 4- token-wallet-balance #done
         */

        route::get('/member/dashboard', [MLMController::class, 'dashboardData']);
        route::get('/member/wallet', [WalletController::class, 'walletData']);

        // rank
        Route::get('rank', [RankController::class, 'myRank']);
        Route::get('/rank/evaluate', [RankController::class, 'evaluateRank']);

        Route::get('sync-user', [AuthController::class, 'syncUserToTradingSociety'])->name('syncUser');

        //validation node => for developer only
        Route::get('/members/validate', [NodeValidationController::class, 'validateNode']);

        // analytics routes
        Route::get('/analytics/countries', [AnalyticsController::class, 'countryAnalytics']);
    });


    // admin routes
    route::prefix('admin')->group(function () {
        route::post('login', [AdminAuthController::class, 'login']);
        route::middleware('auth:admin')->group(function () {
            //logout
            route::post('logout', [AdminAuthController::class, 'logout']);

            route::get('commission-payout-batches', [AdminCommissionPayoutBatchController::class, 'index']); //new
            route::get('commission-payout-batches/{id}', [AdminCommissionPayoutBatchController::class, 'show']); //new

            // Users Management
            route::get('users', [AdminUserController::class, 'index']);
            route::get('users-memberships', [AdminUserController::class, 'usersWithMembership']);
            route::post('user/{id}/edit', [AdminUserController::class, 'editUser']);
            route::post('user/{id}/delete', [AdminUserController::class, 'deleteUser']);
            route::post('user/{id}/activeUser', [AdminUserController::class, 'activeUser']);


            //credit management
            route::post('generate-code', [AdminCreditController::class, 'store']);
            route::post('update-user-credit/{id}', [AdminCreditController::class, 'updateUserCredit']);

            route::get('/members/{memberId}/generate-downline-report', [AdminUserController::class, 'generateDownlineReport']);
            // route::get('/members/{memberId}/all-downline-report' , [AdminUserController::class , 'allDownlineReport']);
        });
    });
});
