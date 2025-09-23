<?php

declare(strict_types=1);

namespace App\Filament\Resources\WebsiteAnalyses\Tables;

use App\Models\Project;
use App\Services\WebsiteAnalysisService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Foundation\Application;

class WebsiteAnalysesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')
                    ->label('Projekt')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('url')
                    ->label('URL')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->url),

                TextColumn::make('analysis_type')
                    ->label('Típus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'seo' => 'primary',
                        'ux' => 'info',
                        'content' => 'success',
                        'technical' => 'warning',
                        'competitor' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'seo' => 'SEO',
                        'ux' => 'UX',
                        'content' => 'Tartalom',
                        'technical' => 'Technikai',
                        'competitor' => 'Versenytárs',
                        default => $state,
                    }),

                TextColumn::make('ai_provider')
                    ->label('AI')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('overall_score')
                    ->label('Pontszám')
                    ->numeric()
                    ->sortable()
                    ->suffix('/100')
                    ->color(fn (?int $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Státusz')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'info',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Kész',
                        'processing' => 'Feldolgozás',
                        'pending' => 'Várakozik',
                        'failed' => 'Sikertelen',
                        default => $state,
                    }),

                TextColumn::make('analyzed_at')
                    ->label('Elemezve')
                    ->dateTime('Y.m.d H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Létrehozva')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('Projekt')
                    ->relationship('project', 'name')
                    ->searchable(),

                SelectFilter::make('analysis_type')
                    ->label('Típus')
                    ->options([
                        'seo' => 'SEO elemzés',
                        'ux' => 'UX elemzés',
                        'content' => 'Tartalom elemzés',
                        'technical' => 'Technikai elemzés',
                        'competitor' => 'Versenytárs elemzés',
                    ]),

                SelectFilter::make('status')
                    ->label('Státusz')
                    ->options([
                        'pending' => 'Várakozik',
                        'processing' => 'Feldolgozás alatt',
                        'completed' => 'Kész',
                        'failed' => 'Sikertelen',
                    ]),

                SelectFilter::make('ai_provider')
                    ->label('AI szolgáltató')
                    ->options(fn (): array => WebsiteAnalysisService::getAvailableAiProviders()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('analyze')
                    ->label('Új elemzés')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->status !== 'processing')
                    ->schema([
                        Select::make('analysis_type')
                            ->label('Elemzés típusa')
                            ->options([
                                'seo' => 'SEO elemzés',
                                'ux' => 'UX elemzés',
                                'content' => 'Tartalom elemzés',
                                'technical' => 'Technikai elemzés',
                                'competitor' => 'Versenytárs elemzés',
                            ])
                            ->required(),

                        TextInput::make('url')
                            ->label('Weboldal URL')
                            ->url()
                            ->required()
                            ->placeholder('https://example.com'),

                        Select::make('ai_provider')
                            ->label('AI szolgáltató')
                            ->options(fn (): array => WebsiteAnalysisService::getAvailableAiProviders())
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $websiteAnalysisService = (new Application())->make(WebsiteAnalysisService::class);

                        try {
                            $project = Filament::getTenant();

                            if (! $project instanceof Project) {
                                return;
                            }

                            $analysis = $websiteAnalysisService->createAnalysis([
                                'project_id' => $project->id,
                                'url' => $data['url'],
                                'analysis_type' => $data['analysis_type'],
                                'ai_provider' => $data['ai_provider'],
                                'ai_model' => WebsiteAnalysisService::getModelForProvider($data['ai_provider']),
                            ]);

                            // Meghívjuk a valós AI szolgáltatást
                            if ($data['ai_provider']) {
                                $websiteAnalysisService->runAiAnalysis($analysis);
                            }

                            Notification::make()
                                ->title('Elemzés elindítva')
                                ->body('A weboldal elemzés sikeresen elindult.')
                                ->success()
                                ->send();
                        } catch (Exception $exception) {
                            Notification::make()
                                ->title('Hiba')
                                ->body('Az elemzés indítása sikertelen: ' . $exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->headerActions([
                Action::make('new_analysis')
                    ->label('Új elemzés')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->schema([
                        TextInput::make('url')
                            ->label('Weboldal URL')
                            ->url()
                            ->required()
                            ->placeholder('https://example.com'),

                        Select::make('analysis_type')
                            ->label('Elemzés típusa')
                            ->options([
                                'seo' => 'SEO elemzés',
                                'ux' => 'UX elemzés',
                                'content' => 'Tartalom elemzés',
                                'technical' => 'Technikai elemzés',
                                'competitor' => 'Versenytárs elemzés',
                            ])
                            ->required(),

                        Select::make('ai_provider')
                            ->label('AI szolgáltató')
                            ->options(fn (): array => WebsiteAnalysisService::getAvailableAiProviders())
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $websiteAnalysisService = new WebsiteAnalysisService();

                        try {
                            $project = Filament::getTenant();

                            if (! $project instanceof Project) {
                                return;
                            }

                            $analysis = $websiteAnalysisService->createAnalysis([
                                'project_id' => $project->id,
                                'url' => $data['url'],
                                'analysis_type' => $data['analysis_type'],
                                'ai_provider' => $data['ai_provider'],
                                'ai_model' => WebsiteAnalysisService::getModelForProvider($data['ai_provider']),
                            ]);

                            // Meghívjuk a valós AI szolgáltatást
                            if ($data['ai_provider']) {
                                $websiteAnalysisService->runAiAnalysis($analysis);
                            }

                            Notification::make()
                                ->title('Elemzés elkészült')
                                ->body('A weboldal elemzés sikeresen elkészült.')
                                ->success()
                                ->send();
                        } catch (Exception $exception) {
                            Notification::make()
                                ->title('Hiba')
                                ->body('Az elemzés indítása sikertelen: ' . $exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
