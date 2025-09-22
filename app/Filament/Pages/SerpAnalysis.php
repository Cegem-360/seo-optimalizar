<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroups;
use App\Models\Keyword;
use App\Models\Project;
use App\Models\SerpAnalysisResult;
use App\Services\Api\ApiServiceManager;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class SerpAnalysis extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.pages.serp-analysis';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroups::SeoTools;

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public ?array $analysisResults = [];

    public bool $isLoading = false;

    public ?string $currentKeyword = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('keyword_ids')
                    ->label('Kulcsszavak kiválasztása')
                    ->placeholder('Válassz ki egy vagy több kulcsszót elemzéshez')
                    ->options(function () {
                        $project = filament()->getTenant();

                        if (! $project instanceof Project) {
                            return [];
                        }

                        return $project->keywords()
                            ->orderBy('keyword')
                            ->pluck('keyword', 'id')
                            ->toArray();
                    })
                    ->multiple()
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Több kulcsszót is kiválaszthatsz egyszerre'),
            ])
            ->statePath('data');
    }

    public function getTitle(): string|Htmlable
    {
        return 'SERP Elemzés';
    }

    public static function getNavigationLabel(): string
    {
        return 'SERP Elemzés';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('analyze')
                ->label('Elemzés futtatása')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action('runAnalysis'),
        ];
    }

    public function runAnalysis(): void
    {
        try {
            $keywordIds = $this->data['keyword_ids'] ?? [];

            if (empty($keywordIds)) {
                Notification::make()
                    ->title('Hiba')
                    ->body('Válassz ki legalább egy kulcsszót!')
                    ->danger()
                    ->send();

                return;
            }

            $project = filament()->getTenant();

            if (! $project instanceof Project) {
                return;
            }

            $manager = ApiServiceManager::forProject($project);

            if (! $manager->hasService('gemini')) {
                Notification::make()
                    ->title('Konfiguráció hiányzik')
                    ->body('A Google Gemini API nincs beállítva ehhez a projekthez!')
                    ->warning()
                    ->send();

                return;
            }

            $keywords = Keyword::query()->whereIn('id', $keywordIds)->get();

            if ($keywords->isEmpty()) {
                Notification::make()
                    ->title('Hiba')
                    ->body('A kiválasztott kulcsszavak nem találhatók!')
                    ->danger()
                    ->send();

                return;
            }

            $this->isLoading = true;
            $this->analysisResults = [];
            $gemini = $manager->getGemini();

            foreach ($keywords as $keyword) {
                $this->currentKeyword = $keyword->keyword;

                // Lekérjük a legfrissebb pozíció adatokat
                $latestRanking = $keyword->latestRanking()->first();

                $analysis = $gemini->analyzeKeywordWithPosition($keyword, $latestRanking);

                if ($analysis !== null && $analysis !== []) {
                    // Mentés az adatbázisba
                    $serpResult = SerpAnalysisResult::query()->create([
                        'project_id' => $project->id,
                        'keyword_id' => $keyword->id,
                        'search_id' => $analysis['search_metadata']['id'] ?? null,
                        'organic_results' => $analysis['organic_results'] ?? [],
                        'serp_metrics' => [
                            'total_results' => $analysis['search_metadata']['total_results'] ?? null,
                            'search_time' => $analysis['search_metadata']['time_taken_displayed'] ?? null,
                            'device' => $analysis['search_metadata']['device'] ?? 'desktop',
                            'location' => $analysis['search_metadata']['google_domain'] ?? null,
                        ],
                        'analysis_data' => [
                            'position_rating' => $analysis['position_rating'] ?? null,
                            'current_position' => $analysis['current_position'] ?? null,
                            'main_competitors' => $analysis['main_competitors'] ?? [],
                            'competitor_advantages' => $analysis['competitor_advantages'] ?? [],
                            'improvement_areas' => $analysis['improvement_areas'] ?? [],
                            'target_position' => $analysis['target_position'] ?? null,
                            'estimated_timeframe' => $analysis['estimated_timeframe'] ?? null,
                            'quick_wins' => $analysis['quick_wins'] ?? [],
                        ],
                        'ai_analysis' => $analysis['detailed_analysis'] ?? null,
                    ]);

                    $this->analysisResults[] = [
                        'keyword' => $keyword->keyword,
                        'current_position' => $analysis['current_position'] ?? null,
                        'checked_at' => $latestRanking->fetched_at ?? null,
                        'analysis' => $analysis,
                        'saved_id' => $serpResult->id,
                    ];
                }

                // Kis szünet API rate limiting miatt
                if (count($keywords) > 1) {
                    sleep(1);
                }
            }

            $this->isLoading = false;
            $this->currentKeyword = null;

            if ($this->analysisResults !== []) {
                Notification::make()
                    ->title('Elemzés kész')
                    ->body(count($this->analysisResults) . ' kulcsszó elemzése elkészült!')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Elemzés sikertelen')
                    ->body('Nem sikerült elemezni a kulcsszavakat.')
                    ->danger()
                    ->send();
            }
        } catch (Exception $exception) {
            $this->isLoading = false;
            $this->currentKeyword = null;

            Notification::make()
                ->title('Hiba történt')
                ->body('Hiba az elemzés során: ' . $exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
