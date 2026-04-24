<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    /**
     * Get country-based analytics for the authenticated user's downline network
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function countryAnalytics()
    {
        $user = Auth::user();

        if (!$user || !$user->member) {
            return response()->json([
                'status' => false,
                'message' => 'User or member not found'
            ], 404);
        }

        // Get all downline members recursively
        $downlines = $user->member->getAllDownlinesNetwork();

        // Extract related users from downlines and include the authenticated user
        $users = $downlines->pluck('user')->filter()->push($user);

        $total = $users->count();

        if ($total === 0) {
            return response()->json([
                'status' => true,
                'data' => []
            ]);
        }

        // Group users by country and calculate analytics
        $analytics = $users->groupBy('country')->map(function ($group, $country) use ($total) {
            return [
                'country' => $country,
                'code' => $this->getCountryCode($country),
                'count' => $group->count(),
                'percentage' => number_format(($group->count() / $total) * 100, 2) . '%',
            ];
        })->values()->sortByDesc('count')->values();

        return response()->json([
            'status' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Convert country name to ISO code or generate a 3-character code
     *
     * @param string $country
     * @return string
     */
    private function getCountryCode($country)
    {
        // Common country name to ISO code mapping
        $countryMapping = [
            'Egypt' => 'EGY',
            'United States' => 'USA',
            'United Kingdom' => 'GBR',
            'Canada' => 'CAN',
            'Australia' => 'AUS',
            'Germany' => 'DEU',
            'France' => 'FRA',
            'Italy' => 'ITA',
            'Spain' => 'ESP',
            'Netherlands' => 'NLD',
            'Belgium' => 'BEL',
            'Switzerland' => 'CHE',
            'Austria' => 'AUT',
            'Sweden' => 'SWE',
            'Norway' => 'NOR',
            'Denmark' => 'DNK',
            'Finland' => 'FIN',
            'Poland' => 'POL',
            'Czech Republic' => 'CZE',
            'Hungary' => 'HUN',
            'Romania' => 'ROU',
            'Bulgaria' => 'BGR',
            'Greece' => 'GRC',
            'Turkey' => 'TUR',
            'Russia' => 'RUS',
            'Ukraine' => 'UKR',
            'Belarus' => 'BLR',
            'Estonia' => 'EST',
            'Latvia' => 'LVA',
            'Lithuania' => 'LTU',
            'Moldova' => 'MDA',
            'Armenia' => 'ARM',
            'Georgia' => 'GEO',
            'Azerbaijan' => 'AZE',
            'Kazakhstan' => 'KAZ',
            'Uzbekistan' => 'UZB',
            'Kyrgyzstan' => 'KGZ',
            'Tajikistan' => 'TJK',
            'Turkmenistan' => 'TKM',
            'China' => 'CHN',
            'Japan' => 'JPN',
            'South Korea' => 'KOR',
            'North Korea' => 'PRK',
            'Taiwan' => 'TWN',
            'Hong Kong' => 'HKG',
            'Singapore' => 'SGP',
            'Malaysia' => 'MYS',
            'Thailand' => 'THA',
            'Vietnam' => 'VNM',
            'Philippines' => 'PHL',
            'Indonesia' => 'IDN',
            'Brunei' => 'BRN',
            'Cambodia' => 'KHM',
            'Laos' => 'LAO',
            'Myanmar' => 'MMR',
            'India' => 'IND',
            'Pakistan' => 'PAK',
            'Bangladesh' => 'BGD',
            'Sri Lanka' => 'LKA',
            'Nepal' => 'NPL',
            'Bhutan' => 'BTN',
            'Maldives' => 'MDV',
            'Afghanistan' => 'AFG',
            'Iran' => 'IRN',
            'Iraq' => 'IRQ',
            'Syria' => 'SYR',
            'Lebanon' => 'LBN',
            'Jordan' => 'JOR',
            'Palestine' => 'PSE',
            'Israel' => 'ISR',
            'Saudi Arabia' => 'SAU',
            'Yemen' => 'YEM',
            'Oman' => 'OMN',
            'United Arab Emirates' => 'ARE',
            'Qatar' => 'QAT',
            'Bahrain' => 'BHR',
            'Kuwait' => 'KWT',
            'Morocco' => 'MAR',
            'Algeria' => 'DZA',
            'Tunisia' => 'TUN',
            'Libya' => 'LBY',
            'Sudan' => 'SDN',
            'South Sudan' => 'SSD',
            'Ethiopia' => 'ETH',
            'Eritrea' => 'ERI',
            'Djibouti' => 'DJI',
            'Somalia' => 'SOM',
            'Kenya' => 'KEN',
            'Uganda' => 'UGA',
            'Tanzania' => 'TZA',
            'Rwanda' => 'RWA',
            'Burundi' => 'BDI',
            'Democratic Republic of Congo' => 'COD',
            'Republic of Congo' => 'COG',
            'Central African Republic' => 'CAF',
            'Chad' => 'TCD',
            'Cameroon' => 'CMR',
            'Equatorial Guinea' => 'GNQ',
            'Gabon' => 'GAB',
            'Sao Tome and Principe' => 'STP',
            'Nigeria' => 'NGA',
            'Niger' => 'NER',
            'Mali' => 'MLI',
            'Burkina Faso' => 'BFA',
            'Benin' => 'BEN',
            'Togo' => 'TGO',
            'Ghana' => 'GHA',
            'Cote d\'Ivoire' => 'CIV',
            'Liberia' => 'LBR',
            'Sierra Leone' => 'SLE',
            'Guinea' => 'GIN',
            'Guinea-Bissau' => 'GNB',
            'Senegal' => 'SEN',
            'Gambia' => 'GMB',
            'Mauritania' => 'MRT',
            'Comoros' => 'COM',
            'Seychelles' => 'SYC',
            'Mauritius' => 'MUS',
            'Madagascar' => 'MDG',
            'Zimbabwe' => 'ZWE',
            'Botswana' => 'BWA',
            'Namibia' => 'NAM',
            'South Africa' => 'ZAF',
            'Lesotho' => 'LSO',
            'Eswatini' => 'SWZ',
            'Mozambique' => 'MOZ',
            'Zambia' => 'ZMB',
            'Malawi' => 'MWI',
            'Angola' => 'AGO',
            'Cuba' => 'CUB',
            'Jamaica' => 'JAM',
            'Haiti' => 'HTI',
            'Dominican Republic' => 'DOM',
            'Puerto Rico' => 'PRI',
            'Trinidad and Tobago' => 'TTO',
            'Barbados' => 'BRB',
            'Bahamas' => 'BHS',
            'Grenada' => 'GRD',
            'Saint Lucia' => 'LCA',
            'Saint Vincent and the Grenadines' => 'VCT',
            'Antigua and Barbuda' => 'ATG',
            'Dominica' => 'DMA',
            'Saint Kitts and Nevis' => 'KNA',
            'Mexico' => 'MEX',
            'Guatemala' => 'GTM',
            'Belize' => 'BLZ',
            'El Salvador' => 'SLV',
            'Honduras' => 'HND',
            'Nicaragua' => 'NIC',
            'Costa Rica' => 'CRI',
            'Panama' => 'PAN',
            'Colombia' => 'COL',
            'Venezuela' => 'VEN',
            'Ecuador' => 'ECU',
            'Peru' => 'PER',
            'Bolivia' => 'BOL',
            'Paraguay' => 'PRY',
            'Uruguay' => 'URY',
            'Argentina' => 'ARG',
            'Chile' => 'CHL',
            'Brazil' => 'BRA',
            'Guyana' => 'GUY',
            'Suriname' => 'SUR',
            'French Guiana' => 'GUF',
            'Falkland Islands' => 'FLK',
            'New Zealand' => 'NZL',
            'Fiji' => 'FJI',
            'Papua New Guinea' => 'PNG',
            'Solomon Islands' => 'SLB',
            'Vanuatu' => 'VUT',
            'New Caledonia' => 'NCL',
            'French Polynesia' => 'PYF',
            'Samoa' => 'WSM',
            'Tonga' => 'TON',
            'Kiribati' => 'KIR',
            'Marshall Islands' => 'MHL',
            'Micronesia' => 'FSM',
            'Palau' => 'PLW',
            'Nauru' => 'NRU',
            'Tuvalu' => 'TUV',
        ];

        // Return mapped code if exists, otherwise generate from country name
        return $countryMapping[$country] ?? strtoupper(substr($country, 0, 3));
    }


    /**
     * Team performance analytics with date filter
     * team performance:
     * - top earners
     * - rank overview
     * - package overview
     * - new members
     */
    public function teamPerformance(Request $request)
    {
        $user = Auth::user();
        $member = $user->member;

        // Get date filter parameters
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $days = $request->get('days', 30);

        // get top earners based on commission
        $topEarners = $member->getTopEarners();

        // get downline members by rank
        $rankOverview = $member->getDownlineDetailsByRank();

        // get package overview
        $packageOverview = $member->getPackageOverview();

        // get new members with date filter
        $newMembers = $member->getNewMembers($days);

        return response()->json([
            'top_earners' => $topEarners,
            'rank_overview' => $rankOverview,
            'package_overview' => $packageOverview,
            'new_members' => $newMembers,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days,
            ]
        ]);
    }
}
