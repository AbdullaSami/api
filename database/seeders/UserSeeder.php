<?php

namespace Database\Seeders;

use App\Models\CommissionFactor;
use App\Models\Package;
use App\Models\User;
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
        User::factory()->create([
            // 'id_code'        => 400000,
            'username'       => fake()->userName(),
            'first_name'     => 'main',
            'last_name'      => 'Account',
            'email'          => 'start.main@hfs.com'
        ])
            ->each(function ($user) {
                // Create a member for each user  
                $member = $user->member()->create([
                    'rank_id'        => 1
                ]);

                // Create a wallet for the created member  
                $member->wallet()->create([
                    'balance' => 0
                ]);


                $member->subscription()->create([
                    'package_id'        => Package::inRandomOrder()->first()->id,
                    'expiration_date'   => now()->addMonth()
                ]);
            });

        CommissionFactor::create([
            'direct_rate' => 20,
            'binary_rate' => 20
        ]);
    }
}
