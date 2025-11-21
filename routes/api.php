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

route::any('login', function () {
    return response()->json('you are unauthorized', 400);
})->name('login');


route::prefix('v1')->group(function () {

    route::post('login', [AuthController::class, 'login']);
    route::post('register', [AuthController::class, 'register']);
    route::get('sponsor-data/{id}', [AuthController::class, 'sponsorData']);

    // single sign-On
    Route::post('login-token', [AuthController::class, 'generateToken']);

    //ranks
    route::get('ranks', [RankController::class, 'ranks']);



    route::middleware('auth:sanctum')->group(function () {
        // logout
        route::post('logout', [AuthController::class, 'logout']);


        // user profile data
        route::get('user/data', [AuthController::class, 'userProfile']);
        route::get('user/data/{id}', [AuthController::class, 'ProfilebyId']);
        route::post('user/edit', [AuthController::class, 'editUserProfile']);
        route::post('user/delete', [AuthController::class, 'deleteMyUser']);
        route::post('user/active', [AuthController::class, 'activeUser']);
        route::post('user/inactive', [AuthController::class, 'inactiveUser']);

        // single sign-On
        Route::get('get-login-user', [AuthController::class, 'getUser']);


        // user reset password
        Route::post('user/password/email', [ResetPasswordController::class, 'sendResetLinkEmail']);
        Route::post('user/password/reset', [ResetPasswordController::class, 'reset'])->name('password.reset');


        //tank
        route::get('user-tank', [MLMController::class, 'mtTank']);

        // members
        Route::post('place-referral', [MLMController::class, 'placeReferral']);
        route::get('all-downline-members', [MLMController::class, 'getDownlineMembers']);
        route::get('direct-downline-members', [MLMController::class, 'getDirectDownlineMembers']);
        route::get('get-direct-downline-members/{id}', [MLMController::class, 'getDirectDownlineMembersById']);
        route::get('left-downline-members', [MLMController::class, 'getLeftDownlineMembers']);
        route::get('right-downline-members', [MLMController::class, 'getRightDownlineMembers']);
        route::get('downline-counts', [MLMController::class, 'getDownlineCounts']);
        route::get('downlines-volume', [MLMController::class, 'getNetworkVolume']);
        Route::get('/members/downlines', [MLMController::class, 'getDownlineDetails']);

        Route::get('/members/yearly-sales', [MLMController::class, 'getYearlySales']);


        //packeage
        Route::get('packages', [PackageController::class, 'index']);
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
        /**
         * abdulla's personal notes:
         * 1 - create checkPassword service to verify user password before internal transfer
         * */


        // rank
        Route::get('rank', [RankController::class, 'myRank']);
        Route::get('/rank/evaluate', [RankController::class, 'evaluateRank']);

        Route::get('sync-user', [AuthController::class, 'syncUserToTradingSociety'])->name('syncUser');

        //validation node => for developer only
        Route::get('/members/validate', [NodeValidationController::class, 'validateNode']);
    });
    // admin routes

    route::prefix('admin')->group(function () {
        route::post('login', [AdminAuthController::class, 'login']);
        route::middleware('auth:admin')->group(function () {
            //logout
            route::post('logout', [AdminAuthController::class, 'logout']);

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
