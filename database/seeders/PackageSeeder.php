<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    public function run()
    {
        $packages    = [
            [
                'name' => 'regester',
                'price' => 0.00,
                'billing_period' => 'Annual',
                'cv' => 0,
                'pack_card' => 'https://dev.thenovagroupco.com/storage/packages/Founder Package Cards-1.png',
                'pack_icon' => 'https://dev.thenovagroupco.com/storage/packages_icons/icon_1.PNG',
                'features' => [
                    "Trade alert" => true,
                    "Beginner course" => true,
                    "Basics course" => true,
                    "Live trading" => true,
                    "Live sessions" => true,
                    "Advance course" => true,
                    "Expert course" => true,
                    "Expert plus course" => true,
                    "All Scanners" => true,
                    "Private sessions with selected coach" => true,
                    "Affiliate program" => true,
                ]
            ],
            [
                'name' => 'nova school',
                'price' => 150.00,
                'billing_period' => 'Monthly',
                'cv' => 0,
                'pack_card' => 'https://dev.thenovagroupco.com/storage/packages/Founder Package Cards-2.png',
                'pack_icon' => 'https://dev.thenovagroupco.com/storage/packages_icons/icon_2.PNG',

                'features' => [
                    "Trade alert" => true,
                    "Beginner course" => true,
                    "Basics course" => true,
                    "Live trading" => true,
                    "Live sessions" => true,
                    "Advance course" => true,
                    "Expert course" => true,
                    "Expert plus course" => true,
                    "All Scanners" => true,
                    "Private sessions with selected coach" => true,
                    "Affiliate program" => true,
                ]
            ],
            [
                'name' => 'Basic PRO+',
                'price' => 570.00,
                'billing_period' => 'Annual',
                'cv' => 500,
                'pack_card' => 'https://dev.thenovagroupco.com/storage/packages/Founder Package Cards-3.png',
                'pack_icon' => 'https://dev.thenovagroupco.com/storage/packages_icons/icon_3.PNG',

                'features' => [
                    "Loyalty program" => true,
                    "Trade alert" => true,
                    "Beginner course" => true,
                    "Basics course" => true,
                    "Live trading" => true,
                    "Live sessions" => true,
                    "Advance course" => true,
                    "Expert course" => true,
                    "Expert plus course" => true,
                    "One Scanners" => true,
                    "Affiliate program" => true,
                ]
            ],
            [
                'name' => 'Premium PRO+',
                'price' => 1140.00,
                'billing_period' => 'Annual',
                'cv' => 1000,
                'pack_card' => 'https://dev.thenovagroupco.com/storage/packages/Founder Package Cards-4.png',
                'pack_icon' => 'https://dev.thenovagroupco.com/storage/packages_icons/icon_2.PNG',
                'features' => [
                    "Loyalty program" => true,
                    "Trade alert" => true,
                    "Beginner course" => true,
                    "Basics course" => true,
                    "Live trading" => true,
                    "Live sessions" => true,
                    "Advance course" => true,
                    "Expert course" => true,
                    "Expert plus course" => true,
                    "One Scanners" => true,
                    "Affiliate program" => true,
                ]
            ],
            [
                'name' => 'PRO+',
                'price' => 3420.00,
                'billing_period' => 'Annual',
                'cv' => 3000,
                'pack_card' => 'https://dev.thenovagroupco.com/storage/packages/Founder Package Cards-5.png',
                'pack_icon' => 'https://dev.thenovagroupco.com/storage/packages_icons/icon_3.PNG',
                'features' => [
                    "Loyalty program" => true,
                    "Trade alert" => true,
                    "Beginner course" => true,
                    "Basics course" => true,
                    "Live trading" => true,
                    "Live sessions" => true,
                    "Advance course" => true,
                    "Expert course" => true,
                    "Expert plus course" => true,
                    "One Scanners" => true,
                    "Affiliate program" => true,
                ]
            ],
            [
                'name' => 'Ultimate PRO+',
                'price' => 5700.00,
                'billing_period' => 'Annual',
                'cv' => 5000,
                'pack_card' => 'https://dev.thenovagroupco.com/storage/packages/Founder Package Cards-6.png',
                'pack_icon' => 'https://dev.thenovagroupco.com/storage/packages_icons/icon_1.PNG',
                'features' => [
                    "Loyalty program" => true,
                    "Trade alert" => true,
                    "Beginner course" => true,
                    "Basics course" => true,
                    "Live trading" => true,
                    "Live sessions" => true,
                    "Advance course" => true,
                    "Expert course" => true,
                    "Expert plus course" => true,
                    "One Scanners" => true,
                    "Affiliate program" => true,
                ]
            ],
            [
                'name' => 'Super PRO+',
                'price' => 11400.00,
                'billing_period' => 'Annual',
                'cv' => 10000,
                'pack_card' => 'https://dev.thenovagroupco.com/storage/packages/Founder Package Cards-1.png',
                'pack_icon' => 'https://dev.thenovagroupco.com/storage/packages_icons/icon_2.PNG',
                'features' => [
                    "Loyalty program" => true,
                    "Trade alert" => true,
                    "Beginner course" => true,
                    "Basics course" => true,
                    "Live trading" => true,
                    "Live sessions" => true,
                    "Advance course" => true,
                    "Expert course" => true,
                    "Expert plus course" => true,
                    "One Scanners" => true,
                    "Affiliate program" => true,
                ]
            ],
            [
                'name' => 'Alpha PRO+',
                'price' => 28500.00,
                'billing_period' => 'Annual',
                'cv' => 25000,
                'pack_card' => 'https://dev.thenovagroupco.com/storage/packages/Founder Package Cards-2.png',
                'pack_icon' => 'https://dev.thenovagroupco.com/storage/packages_icons/icon_3.PNG',
                'features' => [
                    "Loyalty program" => true,
                    "Trade alert" => true,
                    "Beginner course" => true,
                    "Basics course" => true,
                    "Live trading" => true,
                    "Live sessions" => true,
                    "Advance course" => true,
                    "Expert course" => true,
                    "Expert plus course" => true,
                    "One Scanners" => true,
                    "Affiliate program" => true,
                ]
            ],
            [
                'name' => 'Basic',
                'price' => 399.00,
                'billing_period' => 'Annual',
                'cv' => 590,
                'pack_card' => 'https://dev.thenovagroupco.com/storage/packages/Founder Package Cards-3.png',
                'pack_icon' => 'https://dev.thenovagroupco.com/storage/packages_icons/icon_1.PNG',
                'features' => [
                    "Trade alert" => true,
                    "Beginner course" => true,
                    "Basics course" => true,
                    "Live trading" => true,
                    "Live sessions" => true,
                    "Advance course" => true,
                    "Expert course" => true,
                    "Expert plus course" => true,
                    "One Scanners" => true,
                    "Private sessions with selected coach" => true,
                    "Affiliate program" => true,
                    "Affiliate program with extra Bonus" => true,
                ]
            ],
            [
                'name' => 'Ultimate',
                'price' => 749.00,
                'billing_period' => 'Annual',
                'cv' => 1100,
                'pack_card' => 'https://dev.thenovagroupco.com/storage/packages/Founder Package Cards-4.png',
                'pack_icon' => 'https://dev.thenovagroupco.com/storage/packages_icons/icon_2.PNG',
                'features' => [
                    "Trade alert" => true,
                    "Beginner course" => true,
                    "Basics course" => true,
                    "Live trading" => true,
                    "Live sessions" => true,
                    "Advance course" => true,
                    "Expert course" => true,
                    "Expert plus course" => true,
                    "One Scanners" => true,
                    "Private sessions with selected coach" => true,
                    "Affiliate program" => true,
                    "Affiliate program with extra Bonus" => true,
                ]
            ],
            [
                'name' => 'PRO',
                'price' => 1499.00,
                'billing_period' => 'Annual',
                'cv' => 2200,
                'pack_card' => 'https://dev.thenovagroupco.com/storage/packages/Founder Package Cards-5.png',
                'pack_icon' => 'https://dev.thenovagroupco.com/storage/packages_icons/icon_3.PNG',
                'features' => [
                    "Trade alert" => true,
                    "Beginner course" => true,
                    "Basics course" => true,
                    "Live trading" => true,
                    "Live sessions" => true,
                    "Advance course" => true,
                    "Expert course" => true,
                    "Expert plus course" => true,
                    "One Scanners" => true,
                    "Private sessions with selected coach" => true,
                    "Affiliate program" => true,
                    "Affiliate program with extra Bonus" => true,
                ]
            ],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }
    }
}
