<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <div class="space-y-6">
        {{-- Page Description --}}
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-xl border border-blue-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-arrow-path class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            Manual Data Synchronization
                        </h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                            Trigger manual synchronization operations to populate your database with the latest data from external APIs and services.
                            Monitor the progress and results in real-time below.
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                            <x-heroicon-o-cpu-chip class="w-4 h-4 mr-2 text-blue-500" />
                            System Operations
                        </h4>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-start">
                                <span class="w-1.5 h-1.5 bg-blue-400 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                <span><strong>Test API Connections:</strong> Verify all configured services are accessible</span>
                            </li>
                            <li class="flex items-start">
                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                <span><strong>Sync Search Console:</strong> Import keyword rankings and performance data</span>
                            </li>
                            <li class="flex items-start">
                                <span class="w-1.5 h-1.5 bg-yellow-400 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                <span><strong>Update Keywords:</strong> Fetch search volume and difficulty scores</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                            <x-heroicon-o-chart-bar-square class="w-4 h-4 mr-2 text-purple-500" />
                            Analytics Operations
                        </h4>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-start">
                                <span class="w-1.5 h-1.5 bg-purple-400 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                <span><strong>Google Analytics Sync:</strong> Collect 30 days of analytics data day by day</span>
                            </li>
                            <li class="flex items-start">
                                <span class="w-1.5 h-1.5 bg-red-400 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                <span><strong>Historical Metrics:</strong> Detailed historical data (high API usage)</span>
                            </li>
                            <li class="flex items-start">
                                <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                <span><strong>PageSpeed Analysis:</strong> Performance insights and recommendations</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Loading State --}}
        @if ($isLoading)
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <div class="relative">
                                <div class="w-10 h-10 border-4 border-blue-200 dark:border-blue-800 rounded-full"></div>
                                <div class="absolute top-0 left-0 w-10 h-10 border-4 border-blue-600 dark:border-blue-400 border-t-transparent rounded-full animate-spin"></div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-blue-900 dark:text-blue-100">
                                Operation in Progress
                            </h4>
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                Please wait while we process your request...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Results Section --}}
        @if (!empty($syncResults))
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                        <x-heroicon-o-list-bullet class="w-5 h-5 mr-2 text-gray-500" />
                        Sync Results
                    </h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ count($syncResults) }} {{ Str::plural('operation', count($syncResults)) }}
                    </span>
                </div>

                <div class="space-y-3">
                    @foreach ($syncResults as $index => $result)
                        <div class="rounded-xl border @if($result['status'] === 'success') bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-green-200 dark:border-green-800 @elseif($result['status'] === 'error') bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-red-200 dark:border-red-800 @elseif($result['status'] === 'info') bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-blue-200 dark:border-blue-800 @else bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border-yellow-200 dark:border-yellow-800 @endif overflow-hidden transition-all duration-200 hover:shadow-md">
                            <div class="p-5">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center @if($result['status'] === 'success') bg-green-100 dark:bg-green-800/50 @elseif($result['status'] === 'error') bg-red-100 dark:bg-red-800/50 @elseif($result['status'] === 'info') bg-blue-100 dark:bg-blue-800/50 @else bg-yellow-100 dark:bg-yellow-800/50 @endif">
                                                @if($result['status'] === 'success')
                                                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-600 dark:text-green-400" />
                                                @elseif($result['status'] === 'error')
                                                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-600 dark:text-red-400" />
                                                @elseif($result['status'] === 'info')
                                                    <x-heroicon-s-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                                @else
                                                    <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold @if($result['status'] === 'success') text-green-900 dark:text-green-100 @elseif($result['status'] === 'error') text-red-900 dark:text-red-100 @elseif($result['status'] === 'info') text-blue-900 dark:text-blue-100 @else text-yellow-900 dark:text-yellow-100 @endif">
                                                {{ $result['operation'] }}
                                            </h4>
                                            <p class="mt-1 text-sm @if($result['status'] === 'success') text-green-800 dark:text-green-200 @elseif($result['status'] === 'error') text-red-800 dark:text-red-200 @elseif($result['status'] === 'info') text-blue-800 dark:text-blue-200 @else text-yellow-800 dark:text-yellow-200 @endif leading-relaxed">
                                                {{ $result['message'] }}
                                            </p>
                                            @if (!empty($result['output']))
                                                <details class="mt-3 group">
                                                    <summary class="cursor-pointer text-xs @if($result['status'] === 'success') text-green-700 dark:text-green-300 @elseif($result['status'] === 'error') text-red-700 dark:text-red-300 @elseif($result['status'] === 'info') text-blue-700 dark:text-blue-300 @else text-yellow-700 dark:text-yellow-300 @endif hover:underline flex items-center">
                                                        <x-heroicon-o-chevron-right class="w-3 h-3 mr-1 transition-transform group-open:rotate-90" />
                                                        View output details
                                                    </summary>
                                                    <div class="mt-2 p-3 text-xs @if($result['status'] === 'success') bg-green-100 dark:bg-green-800/30 text-green-900 dark:text-green-100 @elseif($result['status'] === 'error') bg-red-100 dark:bg-red-800/30 text-red-900 dark:text-red-100 @elseif($result['status'] === 'info') bg-blue-100 dark:bg-blue-800/30 text-blue-900 dark:text-blue-100 @else bg-yellow-100 dark:bg-yellow-800/30 text-yellow-900 dark:text-yellow-100 @endif rounded-lg border @if($result['status'] === 'success') border-green-200 dark:border-green-700 @elseif($result['status'] === 'error') border-red-200 dark:border-red-700 @elseif($result['status'] === 'info') border-blue-200 dark:border-blue-700 @else border-yellow-200 dark:border-yellow-700 @endif">
                                                        <pre class="overflow-x-auto whitespace-pre-wrap font-mono">{{ trim($result['output']) }}</pre>
                                                    </div>
                                                </details>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 text-xs @if($result['status'] === 'success') text-green-600 dark:text-green-400 @elseif($result['status'] === 'error') text-red-600 dark:text-red-400 @elseif($result['status'] === 'info') text-blue-600 dark:text-blue-400 @else text-yellow-600 dark:text-yellow-400 @endif font-medium">
                                        {{ $result['timestamp'] }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Empty State --}}
        @if (empty($syncResults) && !$isLoading)
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                <div class="p-12 text-center">
                    <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                        <x-heroicon-o-arrow-path class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Ready for Synchronization
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto leading-relaxed">
                        Use the action buttons above to start synchronizing your data from external APIs and services.
                        Results will appear here as operations complete.
                    </p>
                    <div class="mt-6 flex items-center justify-center space-x-1 text-xs text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-light-bulb class="w-4 h-4" />
                        <span>Start with "Test API Connections" to verify your setup</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>