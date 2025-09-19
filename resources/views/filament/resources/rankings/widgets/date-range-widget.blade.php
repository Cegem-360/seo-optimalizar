<x-filament-widgets::widget>
    @php
        $data = $this->getDateRangeData();
    @endphp

    @if($data['hasData'])
        <div class="fi-wi-stats-overview grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            {{-- Date Range Info Card --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex">
                    <div class="flex-1">
                        <div class="flex items-center gap-x-2">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">📅 Data Period</span>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $data['daysSpan'] }} days
                            </div>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $data['startDate'] }} - {{ $data['endDate'] }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                Time range: {{ $data['startTime'] }} - {{ $data['endTime'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Today's Data Card --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex">
                    <div class="flex-1">
                        <div class="flex items-center gap-x-2">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">📊 Today's Rankings</span>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($data['statistics']['today']) }}
                            </div>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Yesterday: {{ number_format($data['statistics']['yesterday']) }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                Daily avg: {{ $data['statistics']['dailyAverage'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Weekly/Monthly Stats Card --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex">
                    <div class="flex-1">
                        <div class="flex items-center gap-x-2">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">📈 Period Statistics</span>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($data['statistics']['thisWeek']) }}
                            </div>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                This week
                            </div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                This month: {{ number_format($data['statistics']['thisMonth']) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Data Coverage Card --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex">
                    <div class="flex-1">
                        <div class="flex items-center gap-x-2">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">📉 Data Coverage</span>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $data['coverage']['coveragePercent'] }}%
                            </div>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $data['coverage']['datesWithData'] }} of {{ $data['coverage']['totalDays'] }} days
                            </div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                Total rankings: {{ number_format($data['statistics']['total']) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-6 text-center">
            <p class="text-gray-500 dark:text-gray-400">No ranking data available for this project.</p>
        </div>
    @endif
</x-filament-widgets::widget>