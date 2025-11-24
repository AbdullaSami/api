<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Commission;

class CommissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = auth()->user();
            $commissions = Commission::query()
                ->where('commissions.sponsor_id', $user->id)

                ->leftJoin('members as r', 'commissions.referral_id', '=', 'r.id')
                ->leftJoin('users as ru', 'r.user_id', '=', 'ru.id') // referral user
                ->select([
                    'commissions.*',
                    'commissions.commission_type',
                    'commissions.created_at',

                    // referral fields
                    'ru.username as referral_username',
                    'ru.id_code as referral_id_code',
                ])
                ->get();
            return response()->json(['commissions' => $commissions], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve commissions', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
