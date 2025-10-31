<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreWalletTransaction extends FormRequest
{
    use ApiResponseTrait;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transaction_type' => ['required', 'string', 'in:withdrawal,deposit'],
            'amount' => ['required', 'numeric']
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->failedResponse($validator->errors(), 422));
    }
}
