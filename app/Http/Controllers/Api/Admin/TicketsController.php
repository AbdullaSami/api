<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tickets;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class TicketsController extends Controller
{
     protected array $statuses = ['open', 'pending', 'answered', 'closed'];
    protected array $priorities = ['low', 'medium', 'high', 'urgent'];

    /**
     * GET /tickets
     */
    public function index(Request $request)
    {
        $query = Tickets::query()->with('user:id,username,email,id_code');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $tickets = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($tickets);
    }

    /**
     * GET /tickets/{ticket}
     */
    public function show(Tickets $ticket)
    {
        return response()->json($ticket->load('user:id,username,email,id_code'));
    }

    /**
     * GET /users/{user}/tickets
     */
    public function forUser(Request $request, int $userId)
    {
        $tickets = Tickets::where('user_id', $userId)
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($tickets);
    }

    /**
     * POST /tickets
     * A member/user opens a new ticket.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', Rule::in($this->priorities)],
        ]);

        $ticket = Tickets::create([
            'user_id' => $validated['user_id'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'open',
        ]);

        return response()->json($ticket->load('user:id,username,email,id_code'), 201);
    }

    /**
     * PUT/PATCH /tickets/{ticket}
     * Edit subject/description/priority (e.g. before staff picks it up).
     */
    public function update(Request $request, Tickets $ticket)
    {
        $validated = $request->validate([
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'priority' => ['sometimes', Rule::in($this->priorities)],
        ]);

        $ticket->update($validated);

        return response()->json($ticket->fresh('user:id,username,email,id_code'));
    }

    /**
     * PATCH /tickets/{ticket}/status
     * Staff-side status transitions, kept separate from the general update()
     * so ticket content edits and status changes have distinct permissions.
     */
    public function updateStatus(Request $request, Tickets $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in($this->statuses)],
        ]);

        $ticket->update(['status' => $validated['status']]);

        return response()->json($ticket->fresh('user:id,username,email,id_code'));
    }

    /**
     * DELETE /tickets/{ticket}
     */
    public function destroy(Tickets $ticket)
    {
        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted.']);
    }
}
