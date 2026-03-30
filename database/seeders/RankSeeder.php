<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rank;

class RankSeeder extends Seeder
{
    public function run()
    {
        $ranks = [
            [
                'name' => 'Executive',
                'package' => 'Executive',
                'left_volume' => 100,
                'right_volume' => 100,
                'direct_referrals' => 2,
                'downline_requirements' => null,
                'icon' => 'https://dev.thenovagroupco.com/storage/rank_icons/Executive.webp',
            ],
            [
                'name' => 'Jade',
                'package' => 'Jade',
                'left_volume' => 500,
                'right_volume' => 500,
                'direct_referrals' => 2,
                'downline_requirements' => null,
                'icon' => 'https://dev.thenovagroupco.com/storage/rank_icons/Jade.webp',
            ],
            [
                'name' => 'Pearl',
                'package' => 'Pearl',
                'left_volume' => 500,
                'right_volume' => 500,
                'direct_referrals' => 2,
                'downline_requirements' => null,
                'icon' => 'https://dev.thenovagroupco.com/storage/rank_icons/pearl.webp',
            ],
            [
                'name' => 'Sapphire',
                'package' => 'Sapphire',
                'left_volume' => 1000,
                'right_volume' => 1000,
                'direct_referrals' => 2,
                'downline_requirements' => null,
                'icon' => 'https://dev.thenovagroupco.com/storage/rank_icons/Sapphire.webp',
            ],
            [
                'name' => 'Ruby',
                'package' => 'Ruby',
                'left_volume' => 8000,
                'right_volume' => 8000,
                'direct_referrals' => 2,
                'downline_requirements' => json_encode(['Sapphire' => 1]), // 1 Sapphire per leg
                'icon' => 'https://dev.thenovagroupco.com/storage/rank_icons/Ruby.webp',
            ],
            [
                'name' => 'Emerald',
                'package' => 'Emerald',
                'left_volume' => 20000,
                'right_volume' => 20000,
                'direct_referrals' => 3,
                'downline_requirements' => json_encode(['Ruby' => 1]), // 1 Ruby per leg
                'icon' => 'https://dev.thenovagroupco.com/storage/rank_icons/Emerald.webp',
            ],
            [
                'name' => 'Diamond',
                'package' => 'Diamond',
                'left_volume' => 40000,
                'right_volume' => 40000,
                'direct_referrals' => 5,
                'downline_requirements' => json_encode(['Emerald' => 1]), // 1 Emerald per leg
                'icon' => 'https://dev.thenovagroupco.com/storage/rank_icons/Diamond.webp',
            ],
            [
                'name' => 'Blue Diamond',
                'package' => 'Blue Diamond',
                'left_volume' => 80000,
                'right_volume' => 80000,
                'direct_referrals' => 6,
                'downline_requirements' => json_encode(['Diamond' => 3, 'min_per_leg' => 1]), // 3 Diamonds, 1 per leg
                'icon' => 'https://dev.thenovagroupco.com/storage/rank_icons/Blue diamond.webp',
            ],
            [
                'name' => 'Black Diamond',
                'package' => 'Black Diamond',
                'left_volume' => 160000,
                'right_volume' => 160000,
                'direct_referrals' => 7,
                'downline_requirements' => json_encode(['Blue Diamond' => 3, 'min_per_leg' => 1]), // 3 Blue Diamonds, 1 per leg
                'icon' => 'https://dev.thenovagroupco.com/storage/rank_icons/Black diamond.webp',
            ],
            [
                'name' => 'Crown',
                'package' => 'Crown',
                'left_volume' => 300000,
                'right_volume' => 300000,
                'direct_referrals' => 8,
                'downline_requirements' => json_encode(['Black Diamond' => 4, 'min_per_leg' => 2]), // 4 Black Diamonds, 2 per leg
                'icon' => 'https://dev.thenovagroupco.com/storage/rank_icons/Crown.webp',
            ],
            [
                'name' => 'Presidential Crown',
                'package' => 'Presidential Crown',
                'left_volume' => 500000,
                'right_volume' => 500000,
                'direct_referrals' => 10,
                'downline_requirements' => json_encode(['Crown' => 4, 'min_per_leg' => 2]), // 4 Crowns, 2 per leg
                'icon' => 'https://dev.thenovagroupco.com/storage/rank_icons/Presidential Crown.webp',
            ],
        ];

        foreach ($ranks as $rank) {
            Rank::create($rank);
        }
    }
}
