<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Oldal tartalma -->
    <div class="space-y-6">
        <!-- Kulcsszó kiválasztási forma -->
        <form wire:submit.prevent="runAnalysis">
            <div class="filament-form">
                {{ $this->form }}
            </div>
        </form>

        <!-- Betöltés animáció -->
        @if ($this->isLoading)
            <div
                class="relative overflow-hidden bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl p-8 text-white shadow-2xl">
                <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                <div class="relative flex items-center justify-center space-x-4">
                    <div class="flex space-x-2">
                        <div class="w-3 h-3 bg-white rounded-full animate-bounce" style="animation-delay: 0ms;"></div>
                        <div class="w-3 h-3 bg-white rounded-full animate-bounce" style="animation-delay: 150ms;"></div>
                        <div class="w-3 h-3 bg-white rounded-full animate-bounce" style="animation-delay: 300ms;"></div>
                    </div>
                    <div>
                        <p class="text-lg font-semibold">AI webes keresés és elemzés folyamatban...</p>
                        @if ($this->currentKeyword)
                            <p class="text-sm opacity-90">Kulcsszó: {{ $this->currentKeyword }}</p>
                            <p class="text-xs opacity-75 mt-1">Valós idejű versenytárs azonosítás...</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Elemzési eredmények -->
        @if (count($this->analysisResults) > 0)
            <div class="space-y-8">
                @foreach ($this->analysisResults as $result)
                    @php
                        $analysis = $result['analysis'];
                        $position = $result['current_position'];
                        $positionClass = match (true) {
                            $position <= 3 => 'from-green-500 to-emerald-600',
                            $position <= 10 => 'from-blue-500 to-cyan-600',
                            $position <= 20 => 'from-yellow-500 to-orange-600',
                            $position <= 50 => 'from-orange-500 to-red-600',
                            default => 'from-gray-500 to-slate-600',
                        };
                    @endphp

                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100"
                        wire:key="result-{{ $loop->index }}">
                        <!-- Modern fejléc gradienssel -->
                        <div class="bg-gradient-to-r {{ $positionClass }} p-6 text-white">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-2xl font-bold mb-2">{{ $result['keyword'] }}</h3>
                                    @if ($result['checked_at'])
                                        <p class="text-sm opacity-90">
                                            <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ \Carbon\Carbon::parse($result['checked_at'])->format('Y. m. d. H:i') }}
                                        </p>
                                    @endif
                                </div>

                                <!-- Pozíció megjelenítés modern stílusban -->
                                <div class="text-right">
                                    @if ($position)
                                        <div
                                            class="inline-flex flex-col items-center bg-white/20 backdrop-blur-md rounded-2xl px-6 py-4">
                                            <span class="text-4xl font-black">{{ $position }}</span>
                                            <span class="text-xs uppercase tracking-wider mt-1">AI pozíció</span>
                                            <span class="text-xs opacity-75 mt-0.5">webes keresés alapján</span>
                                        </div>
                                    @else
                                        <div
                                            class="inline-flex items-center bg-white/20 backdrop-blur-md rounded-xl px-4 py-2">
                                            <span class="text-sm">Nincs adat</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Tartalom modern grid elrendezéssel -->
                        <div class="p-6">
                            <!-- Gyors áttekintés kártyák -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <!-- Értékelés kártya -->
                                @if (isset($analysis['position_rating']))
                                    <div x-data="{
                                        rating: '{{ $analysis['position_rating'] }}',
                                        getClasses() {
                                            const baseClasses = 'bg-gradient-to-br border-2 rounded-2xl p-5 relative overflow-hidden ';
                                            const ratingClasses = {
                                                'kiváló': 'from-green-50 to-emerald-100 border-green-300',
                                                'jó': 'from-blue-50 to-sky-100 border-blue-300',
                                                'közepes': 'from-amber-50 to-yellow-100 border-amber-300',
                                                'gyenge': 'from-orange-50 to-red-100 border-orange-300'
                                            };
                                            return baseClasses + (ratingClasses[this.rating] || 'from-gray-50 to-slate-100 border-gray-300');
                                        },
                                        getBubbleClasses() {
                                            const baseClasses = 'absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 rounded-full opacity-20 ';
                                            const bubbleColors = {
                                                'kiváló': 'bg-green-200',
                                                'jó': 'bg-blue-200',
                                                'közepes': 'bg-amber-200',
                                                'gyenge': 'bg-orange-200'
                                            };
                                            return baseClasses + (bubbleColors[this.rating] || 'bg-gray-200');
                                        }
                                    }" :class="getClasses()">
                                        <div :class="getBubbleClasses()">
                                        </div>
                                        <div class="relative">
                                            <p
                                                class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">
                                                Értékelés</p>
                                            <p
                                                class="text-2xl font-black 
                                                @if ($analysis['position_rating'] == 'kiváló') text-green-700
                                                @elseif($analysis['position_rating'] == 'jó') text-blue-700
                                                @elseif($analysis['position_rating'] == 'közepes') text-amber-700
                                                @elseif($analysis['position_rating'] == 'gyenge') text-orange-700
                                                @else text-gray-700 @endif">
                                                {{ ucfirst($analysis['position_rating']) }}
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                <!-- Célpozíció kártya -->
                                {{--  @if (isset($analysis['target_position']))
                                    <div
                                        class="bg-gradient-to-br from-indigo-50 to-purple-100 border-2 border-indigo-300 rounded-2xl p-5 relative overflow-hidden">
                                        <div
                                            class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-indigo-200 rounded-full opacity-20">
                                        </div>
                                        <div class="relative">
                                            <p
                                                class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">
                                                Célpozíció</p>
                                            <p class="text-2xl font-black text-indigo-700">
                                                {{ $analysis['target_position'] }}. hely</p>
                                        </div>
                                    </div>
                                @endif
 --}}
                                <!-- Időtáv kártya -->
                                {{--  @if (isset($analysis['estimated_timeframe']))
                                    <div class="bg-gradient-to-br from-pink-50 to-rose-100 border-2 border-pink-300 rounded-2xl p-5 relative overflow-hidden">
                                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-pink-200 rounded-full opacity-20"></div>
                                        <div class="relative">
                                            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Időtáv</p>
                                            <p class="text-xl font-black text-pink-700">{{ $analysis['estimated_timeframe'] }}</p>
                                        </div>
                                    </div>
                                @endif --}}
                            </div>

                            <!-- Versenytársak modern megjelenítéssel -->
                            @if (!empty($analysis['main_competitors']))
                                <div class="mb-6">
                                    <h4
                                        class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-purple-500" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                            <path fill-rule="evenodd"
                                                d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 100 4h2a2 2 0 100-4h-.5a1 1 0 000-2H8a2 2 0 012-2h2a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" />
                                        </svg>
                                        AI által azonosított TOP versenytársak
                                    </h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($analysis['main_competitors'] as $index => $competitor)
                                            <div class="group relative">
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-r from-purple-400 to-pink-400 rounded-xl blur opacity-25 group-hover:opacity-40 transition">
                                                </div>
                                                <div
                                                    class="relative bg-white border border-purple-200 rounded-xl px-4 py-2 flex items-center space-x-2 hover:border-purple-400 transition">
                                                    <span
                                                        class="text-xs font-bold text-purple-500">{{ $index + 1 }}.</span>
                                                    <span
                                                        class="text-sm font-semibold text-gray-700">{{ $competitor }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Részletes elemzések modern tab stílusban -->
                            <div class="space-y-4">
                                <!-- Versenytárs előnyök -->
                                @if (!empty($analysis['competitor_advantages']))
                                    <details class="group">
                                        <summary
                                            class="flex items-center justify-between cursor-pointer bg-gradient-to-r from-red-50 to-pink-50 border-2 border-red-200 rounded-xl p-4 hover:border-red-300 transition">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-6 h-6 text-red-500" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-gray-800">Versenytárs előnyök</h4>
                                                    <p class="text-xs text-gray-600">
                                                        {{ count($analysis['competitor_advantages']) }} elem</p>
                                                </div>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </summary>
                                        <div class="mt-4 pl-16 pr-4 pb-4">
                                            <ul class="space-y-2">
                                                @foreach ($analysis['competitor_advantages'] as $advantage)
                                                    <li class="flex items-start">
                                                        <span
                                                            class="inline-block w-2 h-2 bg-red-400 rounded-full mt-1.5 mr-3 flex-shrink-0"></span>
                                                        <span class="text-sm text-gray-700">{{ $advantage }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </details>
                                @endif

                                <!-- Gyors javítások -->

                                <!-- Részletes elemzés -->
                                @if (!empty($analysis['detailed_analysis']))
                                    <div
                                        class="bg-gradient-to-br from-gray-50 to-slate-100 border-2 border-gray-200 rounded-xl p-6">
                                        <div class="flex items-center mb-4">
                                            <div
                                                class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center mr-3">
                                                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                    </path>
                                                </svg>
                                            </div>
                                            <h4 class="font-bold text-gray-800">Részletes elemzés</h4>
                                        </div>
                                        <div class="prose prose-sm max-w-none">
                                            <p class="text-gray-700 leading-relaxed">
                                                {{ $analysis['detailed_analysis'] }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif(!$this->isLoading)
            <!-- Üres állapot modern stílusban -->
            <div
                class="relative bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 rounded-2xl p-12 text-center overflow-hidden">
                <div class="absolute inset-0">
                    <div
                        class="absolute -top-4 -right-4 w-72 h-72 bg-purple-200 rounded-full mix-blend-multiply filter blur-2xl opacity-20 animate-blob">
                    </div>
                    <div
                        class="absolute -bottom-8 -left-4 w-72 h-72 bg-blue-200 rounded-full mix-blend-multiply filter blur-2xl opacity-20 animate-blob animation-delay-2000">
                    </div>
                    <div
                        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-72 h-72 bg-pink-200 rounded-full mix-blend-multiply filter blur-2xl opacity-20 animate-blob animation-delay-4000">
                    </div>
                </div>

                <div class="relative max-w-md mx-auto">
                    <div
                        class="w-20 h-20 bg-white rounded-2xl shadow-lg mx-auto mb-6 flex items-center justify-center">
                        <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">AI Webes Keresés & SERP Elemzés</h3>
                    <p class="text-gray-600 mb-6">
                        Válassz ki egy vagy több kulcsszót. Az AI saját webes keresést végez és azonosítja a valós
                        versenytársakat.
                    </p>
                    <div class="space-y-2">
                        <div class="inline-flex items-center text-sm text-indigo-600 font-medium">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z">
                                </path>
                            </svg>
                            Google Gemini AI webes keresés
                        </div>
                        <div class="inline-flex items-center text-sm text-purple-600 font-medium ml-7">
                            <span class="text-xs">• Valós idejű versenytárs felismerés</span>
                        </div>
                        <div class="inline-flex items-center text-sm text-purple-600 font-medium ml-7">
                            <span class="text-xs">• Aktuális pozíció meghatározás</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }

        .animate-blob {
            animation: blob 7s infinite;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }
    </style>
</x-filament-panels::page>
