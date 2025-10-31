<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition()
    {
        return [
            'user_id' => 1,
            'sponsor_id' => null,
            'left_leg_id' => null,
            'right_leg_id' => null,
            'totla_left_volume' => 0,
            'totla_right_volume' => 0,
        ];
    }
}
