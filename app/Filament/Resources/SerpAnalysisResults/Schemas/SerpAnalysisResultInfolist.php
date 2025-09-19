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
                    ->columns(1),

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
