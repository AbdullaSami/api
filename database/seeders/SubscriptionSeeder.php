<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Subscription::whereNull('code')->orWhere('code', '')->each(function (Subscription $subscription) {
            $date = $subscription->subscribed_at
                ? Carbon::parse($subscription->subscribed_at)->format('Y-m-d')
                : now()->format('Y-m-d');

            do {
                $code = 'SUB-' . $date . '-' . strtoupper(Str::random(4));
            } while (Subscription::where('code', $code)->exists());

            $subscription->update(['code' => $code]);
        });

        $this->command->info('Subscription codes generated successfully.');
    }
}
