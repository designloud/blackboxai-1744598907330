<?php

namespace VendorName\Conversa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use VendorName\Conversa\Models\ConversaMessage;
use VendorName\Conversa\Models\ConversaSpace;
use VendorName\Conversa\Http\Requests\SendConversaMessageRequest;

class ConversaMessageController extends Controller
{
    /**
     * Store a newly created message.
     */
    public function store(SendConversaMessageRequest $request)
    {
        $message = ConversaMessage::create($request->validated());
        return response()->json($message, 201);
    }

    /**
     * Display a listing of messages for a specific space.
     */
    public function index(ConversaSpace $space)
    {
        $messages = $space->messages()->with('user')->latest()->paginate(config('conversa.pagination.messages_per_page'));
        return response()->json($messages);
    }
}
