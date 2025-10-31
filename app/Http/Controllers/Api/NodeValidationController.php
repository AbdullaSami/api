<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use App\Http\Controllers\Controller;

class NodeValidationController extends Controller
{
    /**
     * Validate a specific node and its downline tree.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateNode()
    {
        $user = auth()->user();
        $member = $user->member;

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $problems = $member->validateTree();

        if (empty($problems)) {
            return response()->json(['message' => 'No issues found in the node or its tree.']);
        }

        return response()->json([
            'message' => 'Validation completed with issues.',
            'problems' => $problems,
        ]);
    }
}
