<?php

namespace App\Http\Controllers\Api\Admin\Credits;

use App\Models\User;
use App\Models\CreditCodes;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AdminCreditController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'credit' => ['required', 'numeric', 'max:1999']
        ], [
            'credit.max' => 'The maximum value for extracting a credit code at one time is $1,999, in order to control the flow and prevent errors'
        ]);
        if ($validator->fails())
            return $this->failedResponse($validator->errors(), 422);

        $data = $validator->validated();
        $code = rand(57683910456231, 57683910457231);
        $data['code'] = $code;
        $data['created_by'] = auth('admin')->id();
        try {
            $credirCode =  CreditCodes::create($data);
            return $this->successResponse('the credit code created successfully', 'creadit code', $credirCode);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function updateUserCredit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'credit' => ['required', 'numeric']
        ]);
        if ($validator->fails())
            return $this->failedResponse($validator->errors(), 422);

        $user = User::findOrFail($id);
        $wallet = $user->member->wallet;
        try {
            $wallet->balance += $request->credit;
            $wallet->save();
            return $this->successResponse('the balance updated successfully', 'creadit code', $user->load('member.wallet'));
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }
}
