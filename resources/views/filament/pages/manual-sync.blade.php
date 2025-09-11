<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <div class="space-y-6">
        {{-- Page Description --}}
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <x-heroicon-o-information-circle class="w-6 h-6 text-blue-500" />
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Manual Data Synchronization
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Use the buttons above to manually trigger various data synchronization operations. 
                        Each operation will populate data into your database from external APIs and services.
                    </p>
                    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        <h4 class="font-medium">Available Operations:</h4>
                        <ul class="mt-2 space-y-1 list-disc list-inside">
                            <li><strong>Test API Connections:</strong> Check if all configured API services are working</li>
                            <li><strong>Sync Search Console Data:</strong> Import keyword rankings and performance data</li>
                            <li><strong>Update Keyword Metrics:</strong> Get search volume and difficulty scores</li>
                            <li><strong>Update Historical Metrics:</strong> Get detailed historical keyword data (uses more API credits)</li>
                            <li><strong>Analyze Page Speed:</strong> Run PageSpeed Insights analysis</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Loading State --}}
        @if ($isLoading)
            <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                    </div>
                    <div class="text-sm text-blue-800 dark:text-blue-200">
                        Operation in progress... Please wait.
                    </div>
                </div>
            </div>
        @endif

        {{-- Results Section --}}
        @if (!empty($syncResults))
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Sync Results
                </h3>
                
                @foreach ($syncResults as $index => $result)
                    <div class="p-4 rounded-lg border @if($result['status'] === 'success') bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 @elseif($result['status'] === 'error') bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 @else bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 @endif">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 mt-0.5">
                                    @if($result['status'] === 'success')
                                        <x-heroicon-s-check-circle class="w-5 h-5 text-green-500" />
                                    @elseif($result['status'] === 'error')
                                        <x-heroicon-s-x-circle class="w-5 h-5 text-red-500" />
                                    @else
                                        <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-yellow-500" />
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium @if($result['status'] === 'success') text-green-800 dark:text-green-200 @elseif($result['status'] === 'error') text-red-800 dark:text-red-200 @else text-yellow-800 dark:text-yellow-200 @endif">
                                        {{ $result['operation'] }}
                                    </h4>
                                    <p class="mt-1 text-sm @if($result['status'] === 'success') text-green-700 dark:text-green-300 @elseif($result['status'] === 'error') text-red-700 dark:text-red-300 @else text-yellow-700 dark:text-yellow-300 @endif">
                                        {{ $result['message'] }}
                                    </p>
                                    @if (!empty($result['output']))
                                        <details class="mt-2">
                                            <summary class="cursor-pointer text-xs @if($result['status'] === 'success') text-green-600 dark:text-green-400 @elseif($result['status'] === 'error') text-red-600 dark:text-red-400 @else text-yellow-600 dark:text-yellow-400 @endif hover:underline">
                                                View output details
                                            </summary>
                                            <pre class="mt-2 p-2 text-xs @if($result['status'] === 'success') bg-green-100 dark:bg-green-800/30 text-green-800 dark:text-green-200 @elseif($result['status'] === 'error') bg-red-100 dark:bg-red-800/30 text-red-800 dark:text-red-200 @else bg-yellow-100 dark:bg-yellow-800/30 text-yellow-800 dark:text-yellow-200 @endif rounded overflow-x-auto">{{ trim($result['output']) }}</pre>
                                        </details>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-shrink-0 text-xs @if($result['status'] === 'success') text-green-600 dark:text-green-400 @elseif($result['status'] === 'error') text-red-600 dark:text-red-400 @else text-yellow-600 dark:text-yellow-400 @endif">
                                {{ $result['timestamp'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Empty State --}}
        @if (empty($syncResults) && !$isLoading)
            <div class="p-12 text-center">
                <x-heroicon-o-arrow-path class="mx-auto w-12 h-12 text-gray-400" />
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                    No sync operations performed yet
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Click one of the buttons above to start synchronizing your data.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>