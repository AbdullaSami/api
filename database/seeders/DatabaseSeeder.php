<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CommissionFactor;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([

            PackageSeeder::class,
            RankSeeder::class,
            UserSeeder::class,
            AdminSeeder::class,

        ]);

        $commissionFactor = CommissionFactor::create([
            'direct_rate' => 10,
            'binary_rate' => 10,
        ]);
    }
}
