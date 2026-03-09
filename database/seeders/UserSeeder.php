<?php

namespace Database\Seeders;

use App\Models\CommissionFactor;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use App\Models\UserTank;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // public function run(): void
    // {
    //     User::factory(15)->hasMember()->create();
    // }


    public function run(): void
    {
        $accounts = [
            ['first_name' => 'Nova', 'last_name' => 'Group', 'email' => 'ramezdeeb1994@gmail.com'],
        ];

        foreach ($accounts as $data) {

            $user = User::factory()->create([
                'username' => $data['first_name'] . '_' . $data['last_name'],
                ...$data
            ]);

            // create hash and save pin for user
            $user->pin()->create([
                'pin_hash' => Hash::make('1234'),
            ]);
            $member = $user->member()->create([
                'rank_id' => 1
            ]);

            $member->wallet()->create(['balance' => 10000]);
            $member->tokenWallet()->create(['token_balance' => 10000]);

            $member->subscription()->create([
                'package_id'      => Package::inRandomOrder()->first()->id,
                'expiration_date' => now()->addMonth()
            ]);
        }
    }
}
