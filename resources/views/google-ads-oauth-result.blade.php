<x-layouts.app>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Google Ads OAuth
            </h2>
            <a href="{{ route('google-ads.oauth.start') }}" 
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Start OAuth Process
            </a>
        </div>
    </x-slot>

    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6">
        @if ($success)
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-medium text-gray-900">Success!</h2>
                <p class="mt-2 text-sm text-gray-600">{{ $message }}</p>

                <div class="mt-4 p-4 bg-gray-50 rounded-md">
                    <p class="text-xs text-gray-500 mb-2">Your refresh token:</p>
                    <code class="text-xs break-all text-gray-700">{{ Str::limit($refreshToken, 50) }}...</code>
                </div>

                <div class="mt-6">
                    <p class="text-sm text-gray-600 mb-4">
                        The refresh token has been saved. You can close this window and return to the form.
                    </p>
                    <button onclick="window.close()"
                        class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                        Close Window
                    </button>
                </div>
            </div>

            <script>
                // Try to communicate with the parent window
                if (window.opener) {
                    window.opener.postMessage({
                        type: 'google_ads_oauth_success',
                        refreshToken: '{{ $refreshToken }}'
                    }, '*');

                    // Auto close after 3 seconds
                    setTimeout(() => {
                        window.close();
                    }, 3000);
                }
            </script>
        @else
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-medium text-gray-900">OAuth Error</h2>
                <p class="mt-2 text-sm text-red-600">{{ $error }}</p>

                <div class="mt-6">
                    <button onclick="window.close()"
                        class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                        Close Window
                    </button>
                </div>
            </div>
        @endif
    </div>

</x-layouts.app>
