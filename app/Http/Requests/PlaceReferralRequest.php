<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PlaceReferralRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'placement' => 'required|in:left,right',
            'referral_id' => 'required|exists:members,id'
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        return throw new HttpResponseException(response()->json(
            [
                'message' => $validator->errors()
            ],
            422
        ));
    }
}
