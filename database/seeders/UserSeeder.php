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
$accounts = [
    ['first_name' => 'Mohammed', 'last_name' => 'Hamed', 'email' => '1@hfs.com'],
    ['first_name' => 'Ahmed', 'last_name' => 'Esmail', 'email' => '2@hfs.com'],
    ['first_name' => 'Abdullah', 'last_name' => 'Sami', 'email' => '3@hfs.com'],
];

foreach ($accounts as $data) {

    $user = User::factory()->create([
        'username' => fake()->userName(),
        ...$data
    ]);

    $member = $user->member()->create([
        'rank_id' => 1
    ]);

    $member->wallet()->create(['balance' => 10000]);
    $member->tokenWallet()->create(['token_balance' => 0]);

    $member->subscription()->create([
        'package_id'      => Package::inRandomOrder()->first()->id,
        'expiration_date' => now()->addMonth()
    ]);
}


    }
}
