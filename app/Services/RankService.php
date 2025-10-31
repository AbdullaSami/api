<?php

namespace App\Services;

use App\Models\Rank;

class RankService
{
    public function getRankCriteria()
    {
        return Rank::all()->keyBy('name')->toArray();
    }
}
