@extends('conversa::layout')

@section('threads_list')
    <div class="space-y-1">
        @foreach($threads as $threadItem)
            <a href="{{ route('conversa.threads.show', $threadItem) }}" 
               class="flex items-center px-2 py-2 text-sm font-medium rounded-md {{ $threadItem->id === $thread->id ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <div class="flex items-center space-x-3 flex-1 min-w-0">
                    @if($threadItem->type === 'group')
                        <div class="flex-shrink-0">
                            @if($threadItem->icon_url)
                                <img class="h-6 w-6 rounded-full" src="{{ $threadItem->icon_url }}" alt="">
                            @else
                                <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-users text-gray-500 text-xs"></i>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex-shrink-0">
                            @php
                                $otherUser = $threadItem->members->where('id', '!=', auth()->id())->first();
                            @endphp
                            <img class="h-6 w-6 rounded-full" 
                                 src="{{ $otherUser->avatar_url ?? 'https://images.pexels.com/photos/771742/pexels-photo-771742.jpeg' }}" 
                                 alt="">
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="truncate">{{ $threadItem->getDisplayName() }}</p>
                    </div>
                    @if($threadItem->getUnreadCountFor(auth()->id()) > 0)
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-indigo-600 text-white text-xs">
                                {{ $threadItem->getUnreadCountFor(auth()->id()) }}
                            </span>
                        </div>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endsection

@section('header')
    <div class="flex-1">
        <div class="flex items-center">
            @if($thread->type === 'group')
                <div class="flex-shrink-0">
                    @if($thread->icon_url)
                        <img class="h-10 w-10 rounded-full" src="{{ $thread->icon_url }}" alt="">
                    @else
                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                            <i class="fas fa-users text-gray-500"></i>
                        </div>
                    @endif
                </div>
            @else
                @php
                    $otherUser = $thread->members->where('id', '!=', auth()->id())->first();
                @endphp
                <div class="flex-shrink-0">
                    <img class="h-10 w-10 rounded-full" 
                         src="{{ $otherUser->avatar_url ?? 'https://images.pexels.com/photos/771742/pexels-photo-771742.jpeg' }}" 
                         alt="">
                </div>
            @endif
            <div class="ml-4">
                <h1 class="text-2xl font-semibold text-gray-900">
                    {{ $thread->getDisplayName() }}
                </h1>
                <p class="text-sm text-gray-500">
                    @if($thread->type === 'group')
                        {{ $thread->members->count() }} members
                    @else
                        Active {{ $otherUser->last_seen_at ? $otherUser->last_seen_at->diffForHumans() : 'Never' }}
                    @endif
                </p>
            </div>
        </div>
    </div>
    <div class="flex items-center space-x-4">
        @if($thread->type === 'group')
            <button type="button" id="addMembersButton"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-user-plus mr-2"></i>
                Add Members
            </button>
        @endif
        <div class="relative" x-data="{ open: false }">
            <button type="button" 
                    @click="open = !open"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div x-show="open" 
                 @click.away="open = false"
                 class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100">
                <div class="py-1">
                    <a href="#" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-bell-slash mr-3 text-gray-400 group-hover:text-gray-500"></i>
                        Mute notifications
                    </a>
                    @if($thread->type === 'group')
                        <a href="#" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-3 text-gray-400 group-hover:text-gray-500"></i>
                            Leave group
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="flex flex-col h-full bg-white shadow rounded-lg">
        <!-- Messages Container -->
        <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
            @foreach($messages as $message)
                <div class="flex space-x-3 {{ $message->user_id === auth()->id() ? 'justify-end' : '' }}">
                    @if($message->user_id !== auth()->id())
                        <div class="flex-shrink-0">
                            <img class="h-10 w-10 rounded-full" 
                                 src="{{ $message->user->avatar_url ?? 'https://images.pexels.com/photos/771742/pexels-photo-771742.jpeg' }}" 
                                 alt="{{ $message->user->name }}">
                        </div>
                    @endif
                    
                    <div class="{{ $message->user_id === auth()->id() ? 'bg-indigo-100' : 'bg-gray-100' }} rounded-lg px-4 py-2 max-w-lg">
                        @if($message->user_id !== auth()->id())
                            <div class="font-medium text-gray-900">
                                {{ $message->user->name }}
                                <span class="ml-2 text-sm text-gray-500">{{ $message->created_at->diffForHumans() }}</span>
                            </div>
                        @endif
                        
                        <div class="text-gray-700">
                            {!! nl2br(e($message->content)) !!}
                        </div>

                        @if($message->attachments->isNotEmpty())
                            <div class="mt-2 space-y-2">
                                @foreach($message->attachments as $attachment)
                                    <div class="flex items-center space-x-2 text-sm">
                                        <i class="fas {{ $attachment->getIconClass() }} text-gray-400"></i>
                                        <a href="{{ $attachment->url }}" 
                                           target="_blank"
                                           class="text-indigo-600 hover:text-indigo-900">
                                            {{ $attachment->file_name }}
                                        </a>
                                        <span class="text-gray-500">({{ $attachment->formatted_size }})</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($message->reactions->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($message->getReactionCounts() as $reaction => $count)
                                    <button class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $reaction }} {{ $count }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if($message->user_id === auth()->id())
                        <div class="flex-shrink-0">
                            <img class="h-10 w-10 rounded-full" 
                                 src="{{ auth()->user()->avatar_url ?? 'https://images.pexels.com/photos/771742/pexels-photo-771742.jpeg' }}" 
                                 alt="{{ auth()->user()->name }}">
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Message Input -->
        <div class="border-t border-gray-200 p-4">
            <form id="messageForm" class="flex space-x-4">
                <div class="flex-1">
                    <textarea id="messageInput" 
                              rows="1"
                              placeholder="Type your message..."
                              class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" id="attachmentButton"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('messagesContainer');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');

    // Scroll to bottom of messages
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    scrollToBottom();

    // Handle message submission
    messageForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const content = messageInput.value.trim();
        if (!content) return;

        try {
            const response = await fetch('{{ route("conversa.messages.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    thread_id: '{{ $thread->id }}',
                    content: content
                })
            });

            if (!response.ok) throw new Error('Failed to send message');

            // Clear input
            messageInput.value = '';
            
            // Refresh messages
            // In a real app, you'd append the new message to the DOM
            // and possibly use WebSockets for real-time updates
            location.reload();

        } catch (error) {
            console.error('Error:', error);
            alert('Failed to send message. Please try again.');
        }
    });

    // Auto-expand textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});
</script>
@endpush
