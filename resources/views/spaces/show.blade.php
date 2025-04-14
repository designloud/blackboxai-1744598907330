@extends('conversa::layout')

@section('spaces_list')
    <div class="space-y-1">
        @foreach($spaces as $spaceItem)
            <a href="{{ route('conversa.spaces.show', $spaceItem) }}" 
               class="flex items-center px-2 py-2 text-sm font-medium rounded-md {{ $spaceItem->id === $space->id ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="truncate">
                    @if($spaceItem->type === 'private')
                        <i class="fas fa-lock text-xs mr-1"></i>
                    @else
                        <i class="fas fa-hashtag text-xs mr-1"></i>
                    @endif
                    {{ $spaceItem->name }}
                </span>
            </a>
        @endforeach
    </div>
@endsection

@section('header')
    <div class="flex-1">
        <div class="flex items-center">
            <h1 class="text-2xl font-semibold text-gray-900">
                @if($space->type === 'private')
                    <i class="fas fa-lock text-gray-400 mr-2"></i>
                @else
                    <i class="fas fa-hashtag text-gray-400 mr-2"></i>
                @endif
                {{ $space->name }}
            </h1>
            <span class="ml-4 inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium {{ $space->type === 'private' ? 'bg-gray-100 text-gray-800' : 'bg-green-100 text-green-800' }}">
                {{ ucfirst($space->type) }}
            </span>
        </div>
        <p class="mt-1 text-sm text-gray-500">{{ $space->description }}</p>
    </div>
    <div class="flex items-center space-x-4">
        <button type="button" id="showMembersButton"
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="fas fa-users mr-2"></i>
            {{ $space->members_count ?? 0 }} Members
        </button>
        @if($space->created_by === auth()->id())
            <button type="button" id="spaceSettingsButton"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-cog mr-2"></i>
                Settings
            </button>
        @endif
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
                    space_id: '{{ $space->id }}',
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
