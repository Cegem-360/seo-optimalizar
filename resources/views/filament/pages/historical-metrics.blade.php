<x-filament-panels::page>
    <div class="space-y-6">
        @if(empty($this->selectedKeyword))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                        <x-heroicon-o-chart-bar class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">Nincs kulcsszó kiválasztva</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Válassz egy kulcsszót a "Szűrés" gomb segítségével a történeti adatok megtekintéséhez.</p>
                </div>
            </div>
        @else
            <!-- Kulcsszó információk -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->selectedKeyword['keyword'] }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Projekt: {{ $this->selectedKeyword['project']['name'] ?? 'Nincs projekt' }}</p>
                        @if($this->selectedKeyword['historical_metrics_updated_at'])
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Utolsó frissítés: {{ \Carbon\Carbon::parse($this->selectedKeyword['historical_metrics_updated_at'])->format('Y-m-d H:i') }}
                            </p>
                        @endif
                    </div>
                    <div class="text-right">
                        @if($this->selectedKeyword['search_volume'])
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($this->selectedKeyword['search_volume']) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Jelenlegi keresési volumen</div>
                        @endif
                    </div>
                </div>
            </div>

            @if(!empty($this->chartData))
                <!-- Grafikon -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Keresési volumen trendje</h4>

                    <div class="h-96">
                        <canvas id="historicalChart"></canvas>
                    </div>
                </div>

                <!-- Adatok táblázat -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Havi adatok</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Dátum
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Keresési volumen
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Változás
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->chartData as $index => $row)
                                    @php
                                        $previousValue = $index > 0 ? $this->chartData[$index - 1]['search_volume'] : null;
                                        $change = $previousValue ? (($row['search_volume'] - $previousValue) / $previousValue * 100) : null;
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $row['formatted_date'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ number_format($row['search_volume']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($change !== null)
                                                @if($change > 0)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        <x-heroicon-s-arrow-up class="w-3 h-3 mr-1" />
                                                        +{{ number_format($change, 1) }}%
                                                    </span>
                                                @elseif($change < 0)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                        <x-heroicon-s-arrow-down class="w-3 h-3 mr-1" />
                                                        {{ number_format($change, 1) }}%
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                                        0%
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900">
                            <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">Nincs történeti adat</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            A kiválasztott kulcsszóhoz nem állnak rendelkezésre történeti metrikák.
                        </p>
                    </div>
                </div>
            @endif
        @endif
    </div>

    @if(!empty($this->chartData))
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('historicalChart').getContext('2d');
                const chartData = @json($this->chartData);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.map(item => item.formatted_date),
                        datasets: [{
                            label: 'Keresési volumen',
                            data: chartData.map(item => item.search_volume),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('hu-HU').format(value);
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        elements: {
                            point: {
                                radius: 4,
                                hoverRadius: 6
                            }
                        }
                    }
                });
            });
        </script>
    @endif
</x-filament-panels::page>
