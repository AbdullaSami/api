<?php

namespace Database\Seeders;

use App\Models\Commission;
use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UpdateTotalCommissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Updates total_commision for all members based on their commission records.
     */
    public function run(): void
    {
        $members = Member::all();

        foreach ($members as $member) {
            // Sum all commissions for this member (as sponsor)
            $totalCommission = Commission::where('sponsor_id', $member->id)
                ->sum('commission_value');

            // Update the member's total_commision field
            $member->total_commision = $totalCommission;
            $member->save();

            $this->command->info("Updated member ID {$member->id}: total_commision = {$totalCommission}");
        }

        $this->command->info('Total commission update completed for all members.');
    }
}
