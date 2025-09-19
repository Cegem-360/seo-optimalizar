<?php

namespace App\Filament\Resources\SerpAnalysisResults\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SerpAnalysisResultInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Alapadatok')
                    ->schema([
                        TextEntry::make('keyword.keyword')
                            ->label('Kulcsszó'),

                        TextEntry::make('project.name')
                            ->label('Projekt'),

                        TextEntry::make('created_at')
                            ->label('Elemzés dátuma')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(3),

                Section::make('Pozíció elemzés')
                    ->schema([
                        TextEntry::make('analysis_data.position_rating')
                            ->label('Értékelés')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'kiváló' => 'success',
                                'jó' => 'info',
                                'közepes' => 'warning',
                                'gyenge' => 'danger',
                                'kritikus' => 'gray',
                                default => 'gray',
                            }),

                        TextEntry::make('analysis_data.current_position')
                            ->label('Jelenlegi pozíció')
                            ->formatStateUsing(fn ($state) => $state ? "#$state" : 'Nem található'),

                        TextEntry::make('analysis_data.target_position')
                            ->label('Célpozíció')
                            ->formatStateUsing(fn ($state) => $state ? "#$state" : 'Nincs megadva'),

                        TextEntry::make('analysis_data.estimated_timeframe')
                            ->label('Becsült időtáv'),
                    ])
                    ->columns(4),

                Section::make('Versenytársak')
                    ->schema([
                        KeyValueEntry::make('analysis_data.main_competitors')
                            ->label('Fő versenytársak')
                            ->keyLabel('Sorszám')
                            ->valueLabel('Domain'),

                        KeyValueEntry::make('analysis_data.competitor_advantages')
                            ->label('Versenytársi előnyök')
                            ->keyLabel('Sorszám')
                            ->valueLabel('Előny'),
                    ])
                    ->columns(2),

                Section::make('Javítási lehetőségek')
                    ->schema([
                        KeyValueEntry::make('analysis_data.improvement_areas')
                            ->label('Javítási területek')
                            ->keyLabel('Prioritás')
                            ->valueLabel('Terület'),

                        KeyValueEntry::make('analysis_data.quick_wins')
                            ->label('Gyors győzelmek')
                            ->keyLabel('Sorszám')
                            ->valueLabel('Feladat'),
                    ])
                    ->columns(2),

                Section::make('SERP metrikák')
                    ->schema([
                        TextEntry::make('serp_metrics.total_results')
                            ->label('Összes találat')
                            ->numeric()
                            ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'N/A'),

                        TextEntry::make('serp_metrics.search_time')
                            ->label('Keresési idő'),

                        TextEntry::make('serp_metrics.device')
                            ->label('Eszköz'),

                        TextEntry::make('serp_metrics.location')
                            ->label('Lokáció'),
                    ])
                    ->columns(4),

                Section::make('AI elemzés')
                    ->schema([
                        TextEntry::make('ai_analysis')
                            ->label('Részletes elemzés')
                            ->prose()
                            ->columnSpanFull(),
                    ]),

                Section::make('Organikus találatok')
                    ->schema([
                        KeyValueEntry::make('organic_results')
                            ->label('SERP eredmények')
                            ->keyLabel('Pozíció')
                            ->valueLabel('Cím és URL')
                            ->formatStateUsing(function ($state) {
                                if (! is_array($state)) {
                                    return [];
                                }

                                $formatted = [];
                                foreach ($state as $result) {
                                    $position = $result['position'] ?? 'N/A';
                                    $title = $result['title'] ?? 'Nincs cím';
                                    $link = $result['link'] ?? '';
                                    $formatted["#$position"] = "$title ($link)";
                                }

                                return $formatted;
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
