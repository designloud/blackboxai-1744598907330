@extends('conversa::layout')

@section('spaces_list')
    <div class="space-y-1">
        @foreach($spaces as $space)
            <a href="{{ route('conversa.spaces.show', $space) }}" 
               class="flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->route('space')?->id === $space->id ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="truncate">
                    @if($space->type === 'private')
                        <i class="fas fa-lock text-xs mr-1"></i>
                    @else
                        <i class="fas fa-hashtag text-xs mr-1"></i>
                    @endif
                    {{ $space->name }}
                </span>
            </a>
        @endforeach
    </div>
@endsection

@section('header')
    <div class="flex-1">
        <h1 class="text-2xl font-semibold text-gray-900">Spaces</h1>
    </div>
    <div>
        <button type="button" 
                onclick="document.getElementById('createSpaceModal').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="fas fa-plus mr-2"></i>
            New Space
        </button>
    </div>
@endsection

@section('content')
    <div class="bg-white shadow rounded-lg">
        @if($spaces->isEmpty())
            <div class="p-6 text-center">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <i class="fas fa-comments text-3xl"></i>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No spaces</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new space.</p>
                <div class="mt-6">
                    <button type="button"
                            onclick="document.getElementById('createSpaceModal').classList.remove('hidden')"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-plus mr-2"></i>
                        New Space
                    </button>
                </div>
            </div>
        @else
            <div class="overflow-hidden">
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach($spaces as $space)
                        <li class="p-4 hover:bg-gray-50">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    @if($space->icon_url)
                                        <img class="h-8 w-8 rounded-md" src="{{ $space->icon_url }}" alt="{{ $space->name }}">
                                    @else
                                        <div class="h-8 w-8 rounded-md bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-hashtag text-gray-500"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $space->name }}
                                        </p>
                                        @if($space->type === 'private')
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                <i class="fas fa-lock text-xs mr-1"></i>
                                                Private
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 truncate">
                                        {{ $space->description }}
                                    </p>
                                </div>
                                <div>
                                    <a href="{{ route('conversa.spaces.show', $space) }}" 
                                       class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        View
                                    </a>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Create Space Modal -->
    <div id="createSpaceModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-plus text-indigo-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Create New Space
                        </h3>
                    </div>
                </div>

                <form action="{{ route('conversa.spaces.store') }}" method="POST" class="mt-6">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" id="name" required
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3"
                                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                            <select name="type" id="type" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Create Space
                        </button>
                        <button type="button"
                                onclick="document.getElementById('createSpaceModal').classList.add('hidden')"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
