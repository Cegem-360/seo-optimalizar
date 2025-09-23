<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetitorAnalyses\Pages;

use App\Filament\Resources\CompetitorAnalyses\CompetitorAnalysisResource;
use App\Models\Keyword;
use App\Services\Api\CompetitorAnalysisService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class ListCompetitorAnalyses extends ListRecords
{
    protected static string $resource = CompetitorAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('analyzeCompetitors')
                ->label('Versenytárs Elemzés Indítása')
                ->icon(Heroicon::Play)
                ->color('success')
                ->schema([
                    Select::make('project_id')
                        ->label('Projekt (opcionális)')
                        ->relationship('project', 'name')
                        ->placeholder('Minden projekt'),
                    Select::make('keyword_id')
                        ->label('Kulcsszó (opcionális)')
                        ->options(function (callable $get) {
                            if ($projectId = $get('project_id')) {
                                return Keyword::query()->where('project_id', $projectId)
                                    ->pluck('keyword', 'id');
                            }

                            return Keyword::query()->pluck('keyword', 'id');
                        })
                        ->searchable()
                        ->placeholder('Minden kulcsszó'),
                    TextInput::make('limit')
                        ->label('Versenytársak száma kulcsszavanként')
                        ->numeric()
                        ->default(10)
                        ->minValue(1)
                        ->maxValue(50)
                        ->required(),
                ])
                ->action(function (array $data, CompetitorAnalysisService $competitorAnalysisService): void {
                    $command = 'seo:analyze-competitors';
                    $options = ['--limit' => $data['limit']];

                    if (! empty($data['project_id'])) {
                        $options['--project'] = $data['project_id'];
                    }

                    if (! empty($data['keyword_id'])) {
                        $options['--keyword'] = $data['keyword_id'];
                    }

                    try {
                        Artisan::call($command, $options);

                        Notification::make()
                            ->title('Versenytárs elemzés elindítva')
                            ->body('Az elemzés sikeresen elindult a háttérben.')
                            ->success()
                            ->send();

                        $this->redirect(static::getUrl());
                    } catch (Exception $exception) {
                        Notification::make()
                            ->title('Hiba történt')
                            ->body('Az elemzés elindítása során hiba történt: ' . $exception->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Versenytárs Elemzés Indítása')
                ->modalSubmitActionLabel('Elemzés Indítása')
                ->modalCancelActionLabel('Mégse'),
            CreateAction::make(),
        ];
    }
}
