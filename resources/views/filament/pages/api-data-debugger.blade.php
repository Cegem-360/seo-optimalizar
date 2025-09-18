<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <div class="space-y-6">
        @if ($this->errorMessage)
            <div class="rounded-lg bg-danger-50 p-4 text-danger-900 dark:bg-danger-900/10 dark:text-danger-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-x-circle class="h-5 w-5 text-danger-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium">Error</h3>
                        <div class="mt-2 text-sm">
                            <p>{{ $this->errorMessage }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Service Tabs --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="$set('selectedService', 'search_console')" @class([
                    'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                    'border-primary-500 text-primary-600 dark:text-primary-400' =>
                        $selectedService === 'search_console',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' =>
                        $selectedService !== 'search_console',
                ])>
                    Search Console
                </button>
                <button wire:click="$set('selectedService', 'analytics')" @class([
                    'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                    'border-primary-500 text-primary-600 dark:text-primary-400' =>
                        $selectedService === 'analytics',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' =>
                        $selectedService !== 'analytics',
                ])>
                    Analytics
                </button>
                <button wire:click="$set('selectedService', 'google_ads')" @class([
                    'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                    'border-primary-500 text-primary-600 dark:text-primary-400' =>
                        $selectedService === 'google_ads',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' =>
                        $selectedService !== 'google_ads',
                ])>
                    Google Ads
                </button>
            </nav>
        </div>

        {{-- Search Console Data --}}
        @if ($selectedService === 'search_console' && $searchConsoleData)
            <div class="space-y-6">
                {{-- Metadata --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Search Console Metadata</h3>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                        @foreach ($searchConsoleData['metadata'] as $key => $value)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Aggregated Metrics --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Aggregated Metrics</h3>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-4">
                        @foreach ($searchConsoleData['aggregated_metrics'] as $key => $value)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-200">
                                    {{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Raw Data Table --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Raw Data
                        ({{ count($searchConsoleData['rows']) }} rows)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Query</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Page</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Country</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Device</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Clicks</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Impressions</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        CTR</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Position</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($searchConsoleData['rows'] as $row)
                                    <tr>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ Str::limit($row['query'], 30) }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ Str::limit($row['page'], 40) }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ $row['country'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ $row['device'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ $row['clicks'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ $row['impressions'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ $row['ctr'] }}</td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-200">
                                            {{ $row['position'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- JSON View --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Raw JSON Data</h3>
                    <pre class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto text-xs text-gray-800 dark:text-gray-200"><code>{{ json_encode($searchConsoleData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>
        @elseif($selectedService === 'search_console')
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg">
                <x-heroicon-o-magnifying-glass class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Search Console data</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Click "Fetch Search Console Data" to load data.
                </p>
            </div>
        @endif

        {{-- Analytics Data --}}
        @if ($selectedService === 'analytics' && $analyticsData)
            <div class="space-y-6">
                {{-- Metadata --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Analytics Metadata</h3>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                        @foreach ($analyticsData['metadata'] as $key => $value)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200">
                                    @if(is_array($value))
                                        {{ json_encode($value) }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Totals --}}
                @if(!empty($analyticsData['totals']))
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Analytics Totals</h3>
                        @foreach ($analyticsData['totals'] as $index => $total)
                            <div class="mb-4">
                                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Total Set {{ $index + 1 }}</h4>
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-4">
                                    @foreach ($total as $metric => $value)
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $metric)) }}</dt>
                                            <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-200">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Raw Data Table --}}
                @if(!empty($analyticsData['rows']))
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Analytics Raw Data ({{ count($analyticsData['rows']) }} rows)</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Source</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Medium</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Device</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sessions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Users</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Page Views</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bounce Rate</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($analyticsData['rows'] as $row)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $row['dimensions']['date'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ Str::limit($row['dimensions']['sessionSource'] ?? 'N/A', 20) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $row['dimensions']['sessionMedium'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $row['dimensions']['deviceCategory'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-200">
                                                {{ $row['metrics']['sessions'] ?? 0 }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $row['metrics']['totalUsers'] ?? 0 }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $row['metrics']['screenPageViews'] ?? 0 }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ isset($row['metrics']['bounceRate']) ? round($row['metrics']['bounceRate'] * 100, 2) . '%' : 'N/A' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Raw JSON Data --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Raw JSON Data</h3>
                    <pre class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto text-xs text-gray-800 dark:text-gray-200"><code>{{ json_encode($analyticsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>
        @elseif($selectedService === 'analytics')
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg">
                <x-heroicon-o-chart-bar class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Analytics data</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Click "Fetch Analytics Data" to load data.</p>
            </div>
        @endif

        {{-- Google Ads Data --}}
        @if ($selectedService === 'google_ads' && $googleAdsData)
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Google Ads Data</h3>
                    <pre class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto text-xs text-gray-800 dark:text-gray-200"><code>{{ json_encode($googleAdsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>
        @elseif($selectedService === 'google_ads')
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg">
                <x-heroicon-o-currency-dollar class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Google Ads data</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Click "Fetch Google Ads Data" to load data.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
