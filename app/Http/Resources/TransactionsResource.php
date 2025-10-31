<?php

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class TransactionsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sender = $this->sender_member_id ? Member::find($this->sender_member_id) : Member::where('user_id', Auth::id())->first();
        $receiver = $this->receive_member_id ? Member::find($this->receive_member_id) : Member::where('user_id', Auth::id())->first();

        // Check if sender and receiver members are found  
        if (!$sender) {
            $sender = "not found";
        } else {
            $sender = [
                'id' => $sender->user->id,
                'id_code' => $sender->user->id_code,
                'username' => $sender->user->username,
            ];
        }

        if (!$receiver) {
            $receiver = "not found";
        } else {
            $receiver = [
                'id' => $receiver->user->id,
                'id_code' => $receiver->user->id_code,
                'username' => $receiver->user->username,
            ];
        }

        return [
            "id" => $this->id,
            "wallet_id" => $this->wallet_id,
            "transaction_type" => $this->transaction_type,
            "amount" => $this->amount,
            "status" => $this->status,
            "sender_member_id" => $this->sender_member_id,
            "receive_member_id" => $this->receive_member_id,
            "credit_code" => $this->credit_code,
            "package_name" => $this->package_name,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            'sender' => $sender,
            'receiver' => $receiver,
        ];
    }
}
