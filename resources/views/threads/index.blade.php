@extends('conversa::layout')

@section('threads_list')
    <div class="space-y-1">
        @foreach($threads as $threadItem)
            <a href="{{ route('conversa.threads.show', $threadItem) }}" 
               class="flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->route('thread')?->id === $threadItem->id ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
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
        <h1 class="text-2xl font-semibold text-gray-900">Direct Messages</h1>
    </div>
    <div>
        <button type="button" 
                onclick="document.getElementById('createThreadModal').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="fas fa-plus mr-2"></i>
            New Message
        </button>
    </div>
@endsection

@section('content')
    <div class="bg-white shadow rounded-lg">
        @if($threads->isEmpty())
            <div class="p-6 text-center">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <i class="fas fa-comments text-3xl"></i>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No messages yet</h3>
                <p class="mt-1 text-sm text-gray-500">Start a conversation with someone.</p>
                <div class="mt-6">
                    <button type="button"
                            onclick="document.getElementById('createThreadModal').classList.remove('hidden')"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-plus mr-2"></i>
                        New Message
                    </button>
                </div>
            </div>
        @else
            <ul role="list" class="divide-y divide-gray-200">
                @foreach($threads as $thread)
                    <li class="p-4 hover:bg-gray-50">
                        <a href="{{ route('conversa.threads.show', $thread) }}" class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                @if($thread->type === 'group')
                                    @if($thread->icon_url)
                                        <img class="h-12 w-12 rounded-full" src="{{ $thread->icon_url }}" alt="">
                                    @else
                                        <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-users text-gray-500"></i>
                                        </div>
                                    @endif
                                @else
                                    @php
                                        $otherUser = $thread->members->where('id', '!=', auth()->id())->first();
                                    @endphp
                                    <img class="h-12 w-12 rounded-full" 
                                         src="{{ $otherUser->avatar_url ?? 'https://images.pexels.com/photos/771742/pexels-photo-771742.jpeg' }}" 
                                         alt="">
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $thread->getDisplayName() }}
                                    </p>
                                    <div class="ml-2 flex-shrink-0 flex">
                                        @if($thread->getUnreadCountFor(auth()->id()) > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                {{ $thread->getUnreadCountFor(auth()->id()) }} new
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @if($thread->messages->isNotEmpty())
                                    <p class="mt-1 text-sm text-gray-500 truncate">
                                        {{ $thread->messages->last()->content }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <!-- Create Thread Modal -->
    <div id="createThreadModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-comments text-indigo-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            New Message
                        </h3>
                    </div>
                </div>

                <form action="{{ route('conversa.threads.store') }}" method="POST" class="mt-6">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="users" class="block text-sm font-medium text-gray-700">Select Users</label>
                            <select name="users[]" id="users" multiple required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @foreach($users as $user)
                                    @if($user->id !== auth()->id())
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
                            <textarea name="message" id="message" rows="3" required
                                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Send Message
                        </button>
                        <button type="button"
                                onclick="document.getElementById('createThreadModal').classList.add('hidden')"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
