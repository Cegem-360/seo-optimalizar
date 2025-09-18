<?php

namespace App\Filament\Resources\WebsiteAnalyses\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Exception;
use App\Filament\Resources\WebsiteAnalyses\WebsiteAnalysisResource;
use App\Models\WebsiteAnalysis;
use App\Services\WebsiteAnalysisService;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewWebsiteAnalysis extends ViewRecord
{
    protected static string $resource = WebsiteAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('reanalyze')
                ->label('Újraelemzés')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Elemzés újrafuttatása')
                ->modalDescription('Biztosan újra szeretnéd futtatni az elemzést? Ez felülírja a jelenlegi eredményeket.')
                ->modalSubmitActionLabel('Igen, újraelemzés')
                ->action(function (): void {
                    $this->runReanalysis();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Alapadatok')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('url')
                                    ->label('URL')
                                    ->copyable(),

                                TextEntry::make('analysis_type')
                                    ->label('Elemzés típusa')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'seo' => 'SEO elemzés',
                                        'ux' => 'UX elemzés',
                                        'content' => 'Tartalom elemzés',
                                        'technical' => 'Technikai elemzés',
                                        'competitor' => 'Versenytárs elemzés',
                                        default => $state,
                                    }),

                                TextEntry::make('ai_provider')
                                    ->label('AI szolgáltató'),

                                TextEntry::make('status')
                                    ->label('Státusz')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'gray',
                                        'processing' => 'warning',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => 'Várakozik',
                                        'processing' => 'Feldolgozás alatt',
                                        'completed' => 'Kész',
                                        'failed' => 'Sikertelen',
                                        default => $state,
                                    }),
                            ]),
                    ]),

                Section::make('Eredmények')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('overall_score')
                                    ->label('Összpontszám')
                                    ->suffix('/100')
                                    ->color(fn (?int $state): string => match (true) {
                                        $state === null => 'gray',
                                        $state >= 80 => 'success',
                                        $state >= 60 => 'warning',
                                        default => 'danger',
                                    }),

                                TextEntry::make('analyzed_at')
                                    ->label('Elemzés időpontja')
                                    ->dateTime(),

                                TextEntry::make('sections_count')
                                    ->label('Szakaszok száma')
                                    ->getStateUsing(fn ($record) => $record->sections->count()),
                            ]),

                        KeyValueEntry::make('scores')
                            ->label('Részpontszámok')
                            ->visible(fn ($record): bool => ! empty($record->scores)),
                    ])
                    ->visible(fn ($record): bool => $record->status === 'completed'),

                Section::make('Elemzési szakaszok')
                    ->schema([
                        TextEntry::make('sections')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                if ($record->sections->isEmpty()) {
                                    return 'Nincs elemzési szakasz';
                                }

                                return $record->sections->map(function ($section): string {
                                    $score = $section->score ? sprintf(' (%s/100)', $section->score) : '';
                                    $status = match ($section->status) {
                                        'good' => '✅',
                                        'warning' => '⚠️',
                                        'error' => '❌',
                                        default => '◯',
                                    };

                                    return "**{$status} {$section->section_name}{$score}**\n\n" .
                                           ($section->summary ? $section->summary . '

' : '') .
                                           "**Megállapítások:**\n" .
                                           collect($section->findings)->map(fn ($finding): string => '• ' . $finding)->implode("\n") .
                                           "\n\n**Javaslatok:**\n" .
                                           collect($section->recommendations)->map(fn ($rec): string => '• ' . $rec)->implode("\n");
                                })->implode("\n\n---\n\n");
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record): bool => $record->status === 'completed' && $record->sections->isNotEmpty()),

                Section::make('AI válasz')
                    ->schema([
                        TextEntry::make('raw_response')
                            ->label('Nyers válasz')
                            ->columnSpanFull()
                            ->markdown(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record): bool => ! empty($record->raw_response)),
            ]);
    }

    private function runReanalysis(): void
    {
        try {
            $service = app(WebsiteAnalysisService::class);

            /** @var WebsiteAnalysis $record */
            $record = $this->record;

            // Státusz visszaállítása
            $record->update([
                'status' => 'processing',
                'analyzed_at' => null,
                'overall_score' => null,
                'scores' => null,
                'metadata' => null,
                'raw_response' => null,
                'error_message' => null,
            ]);

            // Szakaszok törlése
            $record->sections()->delete();

            // Új elemzés futtatása
            $demoResponse = $service->getDemoResponse($record->analysis_type);
            $service->processAiResponse($record, $demoResponse);

            // Oldal újratöltése
            redirect()->to($this->getResource()::getUrl('view', ['record' => $this->record]));

            Notification::make()
                ->title('Újraelemzés sikeresen elkészült!')
                ->success()
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->title('Hiba történt az újraelemzés során')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
