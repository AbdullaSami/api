<?php

namespace App\Http\Controllers\Api\MailBox;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TreeService;
use App\Models\MemberMessage;
use App\Models\MemberMessageRecipient;
use Illuminate\Http\Request;

class MemberMessageController extends Controller
{
    protected $treeService;

    public function __construct(TreeService $treeService)
    {
        $this->treeService = $treeService;
    }

    public function searchMembers(Request $request)
    {
        $members = User::query()
            ->where('id', '!=', auth()->user()->id)
            ->where('username', 'like', '%' . $request->search . '%')
            ->get(['id', 'id_code', 'username', 'email', 'image']);

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    public function inbox(Request $request)
    {
        $messages = MemberMessageRecipient::query()

            ->where('recipient_id', auth()->id())

            ->where('deleted_by_recipient', false)

            ->with([
                'message.sender:id,id_code,username,email',
            ])

            ->latest()

            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    public function sent(Request $request)
    {
        $messages = MemberMessage::query()

            ->where('sender_id', auth()->id())

            ->with([
                'recipients.recipient:id,id_code,username,email',
            ])

            ->withCount('recipients')

            ->latest()

            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    public function trash(Request $request)
    {
        $messages = MemberMessageRecipient::query()

            ->where('recipient_id', auth()->id())

            ->where('deleted_by_recipient', true)

            ->with([
                'message.sender:id,id_code,username,email',
            ])

            ->latest()

            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    public function show($id)
    {
        $messageRecipient = MemberMessageRecipient::query()

            ->where('recipient_id', auth()->user()->id)

            ->with([
                'message.sender:id,id_code,username,email',
                'message.attachments',
            ])

            ->firstOrFail();

        if (!$messageRecipient->is_read) {

            $messageRecipient->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $messageRecipient,
        ]);
    }

    public function compose(Request $request, TreeService $treeService)
    {
        $request->validate([
            'subject' => 'nullable|string|max:255',

            'body' => 'required|string',

            'delivery_type' => 'required|in:direct,upline,downline',

            'tree_side' => 'nullable|in:left,right,both',

            'recipient_ids' => 'nullable|array',

            'recipient_ids.*' => 'exists:users,id',
        ]);

        $recipientIds = collect();

        switch ($request->delivery_type) {

            case 'direct':

                $recipientIds = collect($request->recipient_ids);

                break;

            case 'downline':

                $recipientIds = $treeService->getDownlines(
                    auth()->id(),
                    $request->tree_side ?? 'both'
                );

                break;

            case 'upline':

                $recipientIds = $treeService->getUplines(
                    auth()->id(),
                    $request->tree_side ?? 'both'
                );

                break;
        }

        $recipientIds = $recipientIds
            ->filter(fn($id) => $id != auth()->id())
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {

            return response()->json([
                'success' => false,
                'message' => 'No recipients found',
            ], 422);
        }

        $message = MemberMessage::create([
            'sender_id' => auth()->id(),

            'subject' => $request->subject,

            'body' => $request->body,

            'delivery_type' => $request->delivery_type,

            'tree_side' => $request->tree_side ?? 'both',
        ]);

        $recipientData = [];

        foreach ($recipientIds as $recipientId) {

            $recipientData[] = [
                'message_id' => $message->id,
                'recipient_id' => $recipientId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        MemberMessageRecipient::insert($recipientData);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message,
        ]);
    }

    public function markAsRead($id)
    {
        $message = MemberMessageRecipient::query()

            ->where('recipient_id', auth()->id())

            ->where('message_id', $id)

            ->firstOrFail();

        $message->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
        ]);
    }

    public function moveToTrash($id)
    {
        $message = MemberMessageRecipient::query()

            ->where('recipient_id', auth()->id())

            ->where('message_id', $id)

            ->firstOrFail();

        $message->update([
            'deleted_by_recipient' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message moved to trash',
        ]);
    }

    public function restore($id)
    {
        $message = MemberMessageRecipient::query()

            ->where('recipient_id', auth()->id())

            ->where('message_id', $id)

            ->where('deleted_by_recipient', true)

            ->firstOrFail();

        $message->update([
            'deleted_by_recipient' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message restored successfully',
        ]);
    }
}
