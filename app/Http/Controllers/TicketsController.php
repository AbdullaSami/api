<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketsRequest;
use App\Http\Requests\UpdateTicketsRequest;
use App\Models\Tickets;
use App\Models\User;
use Illuminate\Container\Attributes\Auth;

class TicketsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function showUserTickets()
    {
        try{
            $user = auth()->user();
            $tickets = $user->tickets()->get();

            return response()->json([
                'status' => 'success',
                'data' => $tickets
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve tickets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketsRequest $request)
    {
        try{
            $user = User::findOrFail(auth()->id());
            $validatedData = $request->validate([
                'subject' => 'required|string|max:255',
                'description' => 'required|string'
            ]);

            $user->tickets()->create($validatedData);
            return response()->json([
                'status' => 'success',
                'message' => 'Ticket created successfully'
            ], 201);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Tickets $tickets)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tickets $tickets)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketsRequest $request, Tickets $tickets)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tickets $tickets)
    {
        //
    }
}
