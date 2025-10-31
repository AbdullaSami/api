<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

class PackageController extends Controller
{
    use ApiResponseTrait;
    public function index(): JsonResponse
    {
        $packages = Package::all();
        return response()->json($packages);
    }

    public function show()
    {
        $user = auth()->user();
        $subscription =  $user->member->subscription;
        if (empty($subscription)) {
            return $this->failedResponse('no subscription packege for this user');
        }
        $packages = $user->member->subscription->package;
        if (!empty($packages)) {

            return $this->successResponse('packege get successfully', 'package', $packages , 200);
        }
    }
}
