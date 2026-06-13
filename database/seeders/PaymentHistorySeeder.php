<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\PaymentHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
class PaymentHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::with('member.subscription.package')->get()->each(function (User $user) {
            $member = $user->member;

            if (!$member || !$member->subscription) {
                return;
            }

            $subscription = $member->subscription;
            $package = $subscription->package;

            if (!$package) {
                return;
            }

            $paymentDate = $subscription->subscribed_at
                ? Carbon::parse($subscription->subscribed_at)
                : now();

            $paymentCode = $this->generatePaymentCode($subscription->payment_method ?? 'TOKEN', $paymentDate);

            PaymentHistory::create([
                'user_id'           => $user->id,
                'subscription_code' => $subscription->code,
                'payment_code'      => $paymentCode,
                'payment_method'    => $subscription->payment_method,
                'amount'            => $package->price,
                'payment_date'      => $paymentDate,
            ]);
        });

        $this->command->info('Payment history seeded successfully.');
    }

    private function generatePaymentCode(string $method, Carbon $date): string
    {
        $type = str_contains(strtoupper($method), 'STRIP') ? 'STRIP' : 'TOKEN';
        $prefix = $type . '-' . $date->format('Y-m-d') . '-';

        do {
            $code = $prefix . strtoupper(Str::random(4));
        } while (PaymentHistory::where('payment_code', $code)->exists());

        return $code;
    }
}
