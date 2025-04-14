<?php

namespace VendorName\Conversa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use VendorName\Conversa\Models\ConversaThread;
use VendorName\Conversa\Http\Requests\CreateConversaChannelRequest;

class ConversaThreadController extends Controller
{
    /**
     * Display a listing of the threads.
     */
    public function index()
    {
        $threads = ConversaThread::all(); // Fetch all threads for the authenticated user's workspace
        return view('conversa::threads.index', compact('threads'));
    }

    /**
     * Show the specified thread.
     */
    public function show(ConversaThread $thread)
    {
        return view('conversa::threads.show', compact('thread'));
    }

    /**
     * Store a newly created thread.
     */
    public function store(CreateConversaChannelRequest $request)
    {
        $thread = ConversaThread::create($request->validated());
        return redirect()->route('conversa.threads.index')->with('success', 'Thread created successfully.');
    }

    /**
     * Update the specified thread.
     */
    public function update(CreateConversaChannelRequest $request, ConversaThread $thread)
    {
        $thread->update($request->validated());
        return redirect()->route('conversa.threads.show', $thread)->with('success', 'Thread updated successfully.');
    }

    /**
     * Remove the specified thread.
     */
    public function destroy(ConversaThread $thread)
    {
        $thread->delete();
        return redirect()->route('conversa.threads.index')->with('success', 'Thread deleted successfully.');
    }
}
