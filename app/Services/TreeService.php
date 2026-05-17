<?php

namespace App\Services;

use App\Models\Member;

class TreeService
{
    public function getDownlines($memberId, $side = 'both')
    {
        $result = collect();

        $this->loadDownlines($memberId, $result, $side);

        return $result->unique();
    }

    protected function loadDownlines($memberId, &$result, $side)
    {
        $member = Member::find($memberId);

        if (!$member) {
            return;
        }

        $children = collect();

        if ($side === 'both' || $side === 'left') {
            if ($member->left_leg_id) {
                $leftChild = Member::find($member->left_leg_id);
                if ($leftChild) {
                    $children->push($leftChild);
                }
            }
        }

        if ($side === 'both' || $side === 'right') {
            if ($member->right_leg_id) {
                $rightChild = Member::find($member->right_leg_id);
                if ($rightChild) {
                    $children->push($rightChild);
                }
            }
        }

        foreach ($children as $child) {
            $result->push($child->id);
            $this->loadDownlines($child->id, $result, 'both');
        }
    }

    public function getUplines($memberId, $side = 'both')
    {
        $result = collect();

        $member = Member::find($memberId);

        while ($member) {
            $parent = null;

            // Find which parent has this member as a child
            if ($side === 'both' || $side === 'left') {
                $leftParent = Member::where('left_leg_id', $member->id)->first();
                if ($leftParent) {
                    $parent = $leftParent;
                    $result->push($parent->id);
                }
            }

            if (!$parent && ($side === 'both' || $side === 'right')) {
                $rightParent = Member::where('right_leg_id', $member->id)->first();
                if ($rightParent) {
                    $parent = $rightParent;
                    $result->push($parent->id);
                }
            }

            if (!$parent) {
                break;
            }

            $member = $parent;
        }

        return $result->unique();
    }
}
