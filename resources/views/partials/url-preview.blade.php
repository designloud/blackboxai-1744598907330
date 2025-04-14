<div class="url-preview bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mt-2">
    @if($image)
    <div class="url-preview-image mb-3">
        <img src="{{ $image }}" alt="{{ $title ?? 'Link preview' }}" class="rounded-lg max-h-48 w-auto">
    </div>
    @endif
    
    <div class="url-preview-content">
        @if($title)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
            {{ $title }}
        </h3>
        @endif

        @if($description)
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">
            {{ $description }}
        </p>
        @endif

        <div class="url-preview-meta flex items-center text-xs text-gray-500 dark:text-gray-500">
            @if($site_name)
            <span class="site-name mr-2">{{ $site_name }}</span>
            <span class="mx-1">•</span>
            @endif
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="hover:text-blue-500 dark:hover:text-blue-400 truncate">
                {{ parse_url($url, PHP_URL_HOST) }}
                <svg class="inline-block w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
    </div>
</div>
