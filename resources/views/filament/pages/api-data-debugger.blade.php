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
                {{-- Data Structure Overview --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">GA4 Adatok Struktúrája</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-1 lg:grid-cols-2">
                        @foreach ($analyticsData as $category => $data)
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ ucwords(str_replace('_', ' ', $category)) }}</h4>
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    @if(is_array($data))
                                        {{-- Ha ez egy egyszerű numerikus tömb (mint a traffic_sources, top_pages) --}}
                                        @if(array_keys($data) === range(0, count($data) - 1))
                                            <div class="space-y-2">
                                                <div class="text-xs text-gray-500">Tömbben {{ count($data) }} elem van</div>
                                                @if(count($data) > 0)
                                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-2 rounded text-xs">
                                                        <div class="font-medium text-blue-800 dark:text-blue-300 mb-1">Első elem szerkezete:</div>
                                                        @if(is_array($data[0]))
                                                            @foreach ($data[0] as $key => $value)
                                                                <div class="flex justify-between">
                                                                    <span class="font-mono">{{ $key }}:</span>
                                                                    <span class="text-blue-600 dark:text-blue-400">
                                                                        @if(is_array($value))
                                                                            [{{ implode(', ', array_slice(array_keys($value), 0, 3)) }}{{ count($value) > 3 ? '...' : '' }}]
                                                                        @else
                                                                            {{ gettype($value) }}
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <span>{{ gettype($data[0]) }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            {{-- Ha ez egy asszociatív tömb (mint az overview) --}}
                                            <div class="space-y-1">
                                                @foreach ($data as $key => $value)
                                                    <div class="flex justify-between">
                                                        <span class="font-mono text-xs bg-gray-200 dark:bg-gray-600 px-1 rounded">{{ $key }}</span>
                                                        @if(is_array($value))
                                                            <span class="text-gray-500">(tömb: {{ count($value) }} elem)</span>
                                                        @else
                                                            <span class="text-gray-500">({{ gettype($value) }})</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-500">{{ gettype($data) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Detailed Category Analysis --}}
                @foreach ($analyticsData as $category => $categoryData)
                    @if(is_array($categoryData) && !empty($categoryData))
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                {{ ucwords(str_replace('_', ' ', $category)) }} - Részletes Adatok
                            </h3>

                            {{-- Ha ez egy egyszerű numerikus tömb (mint traffic_sources, top_pages) --}}
                            @if(array_keys($categoryData) === range(0, count($categoryData) - 1))
                                <div class="mb-6">
                                    <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">
                                        Adatsorok ({{ count($categoryData) }} db) - Első 10 sor megjelenítve
                                    </h4>

                                    {{-- Show structure of first row --}}
                                    @if(count($categoryData) > 0)
                                        @php $firstRow = $categoryData[0]; @endphp
                                        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                                            <h5 class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-2">Első elem szerkezete:</h5>
                                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                @if(is_array($firstRow))
                                                    @foreach ($firstRow as $key => $value)
                                                        <div class="text-xs">
                                                            <span class="font-mono bg-blue-100 dark:bg-blue-800 px-1 rounded">{{ $key }}</span>
                                                            @if(is_array($value))
                                                                <span class="text-blue-600 dark:text-blue-400">
                                                                    ({{ count($value) }} elem: {{ implode(', ', array_keys($value)) }})
                                                                </span>
                                                            @else
                                                                <span class="text-blue-600 dark:text-blue-400">({{ gettype($value) }})</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Display first 10 rows in detail --}}
                                    @foreach (array_slice($categoryData, 0, 10) as $rowIndex => $row)
                                        <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border">
                                            <h5 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Elem #{{ $rowIndex + 1 }}</h5>

                                            @if(is_array($row))
                                                @foreach ($row as $section => $sectionData)
                                                    <div class="mb-3">
                                                        <h6 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ $section }}</h6>
                                                        @if(is_array($sectionData))
                                                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                                                @foreach ($sectionData as $key => $value)
                                                                    <div>
                                                                        <dt class="text-xs text-gray-400 font-mono">{{ $key }}</dt>
                                                                        <dd class="text-sm text-gray-900 dark:text-gray-200 font-medium">
                                                                            @if(is_numeric($value) && $value > 1000)
                                                                                {{ number_format($value) }}
                                                                            @elseif(is_numeric($value))
                                                                                {{ $value }}
                                                                            @else
                                                                                {{ Str::limit((string)$value, 40) }}
                                                                            @endif
                                                                        </dd>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $sectionData }}</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-sm text-gray-600 dark:text-gray-300">{{ $row }}</span>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if(count($categoryData) > 10)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                            ... és még {{ count($categoryData) - 10 }} elem (lásd a teljes JSON-t lent)
                                        </p>
                                    @endif
                                </div>
                            @else
                                {{-- Asszociatív tömb kezelése (mint az overview) --}}

                                {{-- Totals if available --}}
                                @if(isset($categoryData['totals']) && is_array($categoryData['totals']))
                                    <div class="mb-6">
                                        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">Összegzések</h4>
                                        @foreach ($categoryData['totals'] as $totalIndex => $total)
                                            <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                <h5 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Összegzés #{{ $totalIndex + 1 }}</h5>
                                                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-4">
                                                    @if(is_array($total))
                                                        @foreach ($total as $metric => $value)
                                                            <div>
                                                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 font-mono">{{ $metric }}</dt>
                                                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-200">
                                                                    {{ is_numeric($value) ? number_format($value) : $value }}
                                                                </dd>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </dl>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Rows if available --}}
                                @if(isset($categoryData['rows']) && is_array($categoryData['rows']) && count($categoryData['rows']) > 0)
                                    <div class="mb-6">
                                        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">
                                            Adatsorok ({{ count($categoryData['rows']) }} db) - Első 5 sor megjelenítve
                                        </h4>

                                        {{-- Show structure of first row --}}
                                        @if(isset($categoryData['rows'][0]))
                                            @php $firstRow = $categoryData['rows'][0]; @endphp
                                            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                                                <h5 class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-2">Első sor szerkezete:</h5>
                                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                    @foreach ($firstRow as $key => $value)
                                                        <div class="text-xs">
                                                            <span class="font-mono bg-blue-100 dark:bg-blue-800 px-1 rounded">{{ $key }}</span>
                                                            @if(is_array($value))
                                                                <span class="text-blue-600 dark:text-blue-400">
                                                                    ({{ count($value) }} elem: {{ implode(', ', array_keys($value)) }})
                                                                </span>
                                                            @else
                                                                <span class="text-blue-600 dark:text-blue-400">({{ gettype($value) }})</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Display first 5 rows in detail --}}
                                        @foreach (array_slice($categoryData['rows'], 0, 5) as $rowIndex => $row)
                                            <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border">
                                                <h5 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Sor #{{ $rowIndex + 1 }}</h5>

                                                @if(is_array($row))
                                                    @foreach ($row as $section => $sectionData)
                                                        <div class="mb-3">
                                                            <h6 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ $section }}</h6>
                                                            @if(is_array($sectionData))
                                                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                                                    @foreach ($sectionData as $key => $value)
                                                                        <div>
                                                                            <dt class="text-xs text-gray-400 font-mono">{{ $key }}</dt>
                                                                            <dd class="text-sm text-gray-900 dark:text-gray-200 font-medium">
                                                                                @if(is_numeric($value) && $value > 1000)
                                                                                    {{ number_format($value) }}
                                                                                @else
                                                                                    {{ Str::limit((string)$value, 30) }}
                                                                                @endif
                                                                            </dd>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <span class="text-sm text-gray-600 dark:text-gray-300">{{ $sectionData }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        @endforeach

                                        @if(count($categoryData['rows']) > 5)
                                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                                ... és még {{ count($categoryData['rows']) - 5 }} sor (lásd a teljes JSON-t lent)
                                            </p>
                                        @endif
                                    </div>
                                @endif

                                {{-- Ha egyszerű asszociatív tömb (mint az overview) --}}
                                @if(!isset($categoryData['totals']) && !isset($categoryData['rows']))
                                    <div class="mb-6">
                                        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">Adatok</h4>
                                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-4">
                                            @foreach ($categoryData as $key => $value)
                                                <div>
                                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 font-mono">{{ $key }}</dt>
                                                    <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-200">
                                                        @if(is_numeric($value))
                                                            {{ number_format($value) }}
                                                        @elseif(is_array($value))
                                                            [tömb: {{ count($value) }} elem]
                                                        @else
                                                            {{ $value }}
                                                        @endif
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    </div>
                                @endif

                                {{-- Other properties --}}
                                @php
                                    $otherKeys = array_diff(array_keys($categoryData), ['totals', 'rows']);
                                @endphp
                                @if(!empty($otherKeys) && (isset($categoryData['totals']) || isset($categoryData['rows'])))
                                    <div>
                                        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">Egyéb Tulajdonságok</h4>
                                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            @foreach ($otherKeys as $key)
                                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 font-mono">{{ $key }}</dt>
                                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200">
                                                        @if(is_array($categoryData[$key]))
                                                            <pre class="text-xs bg-gray-100 dark:bg-gray-600 p-2 rounded mt-1 overflow-x-auto">{{ json_encode($categoryData[$key], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                        @else
                                                            {{ $categoryData[$key] }}
                                                        @endif
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                @endforeach

                {{-- Complete Raw JSON Data --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Teljes GA4 Raw JSON Adatok</h3>
                    <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                        <p class="text-sm text-yellow-800 dark:text-yellow-300">
                            <strong>Figyelem:</strong> Ez a teljes, feldolgozatlan JSON adat a Google Analytics 4 API-ból.
                            Itt láthatod pontosan, milyen struktúrában és nevekkel érkeznek az adatok.
                        </p>
                    </div>
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
