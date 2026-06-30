<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SyncMemberIdsSeeder extends Seeder
{
    /**
     * Tables/columns that reference members.id
     */
    protected array $references = [
        'members' => [
            'sponsor_id',
            'left_leg_id',
            'right_leg_id',
        ],

        'commissions' => [
            'sponsor_id',
            'referral_id',
        ],

        'cv_commissions' => [
            'member_id',
        ],

        'referals' => [
            'sponsor_id',
            'referral_id',
        ],

        'subscriptions' => [
            'member_id',
        ],

        'token_wallets' => [
            'member_id',
        ],

        'token_transactions' => [
            'sender_member_id',
            'receive_member_id',
        ],

        'user_tanks' => [
            'member_id',
            'sponsor_id',
        ],

        'wallets' => [
            'member_id',
        ],
    ];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        /**
         * old member id => new member id (= user_id)
         */
        $mapping = DB::table('members')
            ->select('id', 'user_id')
            ->get()
            ->pluck('user_id', 'id')
            ->toArray();

        echo "Loaded ".count($mapping)." members...\n";

        /**
         * --------------------------------------------------
         * STEP 1
         * Update every foreign key
         * --------------------------------------------------
         */
        foreach ($this->references as $table => $columns) {

            foreach ($columns as $column) {

                foreach ($mapping as $oldId => $newId) {

                    DB::table($table)
                        ->where($column, $oldId)
                        ->update([
                            $column => $newId
                        ]);
                }
            }
        }

        echo "Foreign keys updated.\n";

        /**
         * --------------------------------------------------
         * STEP 2
         * Move PKs to temporary ids
         * --------------------------------------------------
         */

        foreach ($mapping as $oldId => $newId) {

            DB::table('members')
                ->where('id', $oldId)
                ->update([
                    'id' => $newId + 1000000
                ]);
        }

        echo "Temporary IDs assigned.\n";

        /**
         * --------------------------------------------------
         * STEP 3
         * Restore final ids
         * --------------------------------------------------
         */

        foreach ($mapping as $oldId => $newId) {

            DB::table('members')
                ->where('id', $newId + 1000000)
                ->update([
                    'id' => $newId
                ]);
        }

        echo "Primary keys synchronized.\n";

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        echo "Done.\n";
    }
}
