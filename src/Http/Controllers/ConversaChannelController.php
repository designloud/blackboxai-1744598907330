<?php

namespace VendorName\Conversa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use VendorName\Conversa\Models\ConversaSpace;
use VendorName\Conversa\Http\Requests\CreateConversaChannelRequest;

class ConversaChannelController extends Controller
{
    /**
     * Display a listing of the channels.
     */
    public function index()
    {
        $spaces = ConversaSpace::all(); // Fetch all channels for the authenticated user's workspace
        return view('conversa::channels.index', compact('spaces'));
    }

    /**
     * Show the specified channel.
     */
    public function show(ConversaSpace $space)
    {
        return view('conversa::channels.show', compact('space'));
    }

    /**
     * Store a newly created channel.
     */
    public function store(CreateConversaChannelRequest $request)
    {
        $space = ConversaSpace::create($request->validated());
        return redirect()->route('conversa.spaces.index')->with('success', 'Channel created successfully.');
    }

    /**
     * Update the specified channel.
     */
    public function update(CreateConversaChannelRequest $request, ConversaSpace $space)
    {
        $space->update($request->validated());
        return redirect()->route('conversa.spaces.show', $space)->with('success', 'Channel updated successfully.');
    }

    /**
     * Remove the specified channel.
     */
    public function destroy(ConversaSpace $space)
    {
        $space->delete();
        return redirect()->route('conversa.spaces.index')->with('success', 'Channel deleted successfully.');
    }
}
