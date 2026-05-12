<?php

namespace App\Services;

use App\Models\User;

class TreeService
{
    public function getDownlines($userId, $side = 'both')
    {
        $result = collect();

        $this->loadDownlines($userId, $result, $side);

        return $result->unique();
    }

    protected function loadDownlines($userId, &$result, $side)
    {
        $query = User::where('placement_parent_id', $userId);

        if ($side !== 'both') {
            $query->where('placement_position', $side);
        }

        $children = $query->get();

        foreach ($children as $child) {

            $result->push($child->id);

            $this->loadDownlines($child->id, $result, 'both');
        }
    }

    public function getUplines($userId, $side = 'both')
    {
        $result = collect();

        $user = User::find($userId);

        while ($user && $user->placement_parent_id) {

            $parent = User::find($user->placement_parent_id);

            if (!$parent) {
                break;
            }

            if (
                $side === 'both' ||
                $user->placement_position === $side
            ) {
                $result->push($parent->id);
            }

            $user = $parent;
        }

        return $result->unique();
    }
}
