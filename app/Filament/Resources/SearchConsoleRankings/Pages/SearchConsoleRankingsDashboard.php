<?php

namespace App\Filament\Resources\SearchConsoleRankings\Pages;

use App\Filament\Resources\SearchConsoleRankings\SearchConsoleRankingResource;
use App\Filament\Resources\SearchConsoleRankings\Widgets;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class SearchConsoleRankingsDashboard extends Page
{
    protected static string $resource = SearchConsoleRankingResource::class;

    protected string $view = 'filament.resources.search-console-rankings.pages.search-console-rankings-dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Search Console Dashboard';

    public array $filters = [
        'date_from' => null,
        'date_to' => null,
        'device' => 'all',
        'country' => 'all',
        'position_range' => 'all',
    ];

    public function mount(): void
    {
        $this->filters = [
            'date_from' => Carbon::now()->subDays(30)->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d'),
            'device' => 'all',
            'country' => 'all',
            'position_range' => 'all',
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Search Console Dashboard';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filters')
                ->label('Filter Data')
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->schema([
                    DatePicker::make('date_from')
                        ->label('From Date')
                        ->default(Carbon::now()->subDays(30)),
                    DatePicker::make('date_to')
                        ->label('To Date')
                        ->default(Carbon::now()),
                    Select::make('device')
                        ->label('Device')
                        ->options([
                            'all' => 'All Devices',
                            'desktop' => 'Desktop',
                            'mobile' => 'Mobile',
                            'tablet' => 'Tablet',
                        ])
                        ->default('all'),
                    Select::make('country')
                        ->label('Country')
                        ->options([
                            'all' => 'All Countries',
                            'hun' => 'Hungary',
                            'usa' => 'United States',
                            'gbr' => 'United Kingdom',
                            'deu' => 'Germany',
                            'aut' => 'Austria',
                        ])
                        ->default('all'),
                    Select::make('position_range')
                        ->label('Position Range')
                        ->options([
                            'all' => 'All Positions',
                            'top3' => 'Top 3',
                            'top10' => 'Top 10 (First Page)',
                            '11-20' => 'Position 11-20 (Second Page)',
                            '21-50' => 'Position 21-50',
                            '50+' => 'Beyond 50',
                        ])
                        ->default('all'),
                ])
                ->fillForm($this->filters)
                ->action(function (array $data): void {
                    $this->filters = $data;
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\SearchConsoleOverviewWidget::class,
            Widgets\PositionDistributionWidget::class,
            Widgets\ClicksPerformanceWidget::class,
        ];
    }

    public function getFilteredQuery()
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return;
        }

        $query = $project->searchConsoleRankings();

        // Date range filter
        if ($this->filters['date_from']) {
            $query->where('date_from', '>=', $this->filters['date_from']);
        }

        if ($this->filters['date_to']) {
            $query->where('date_to', '<=', $this->filters['date_to']);
        }

        // Device filter
        if ($this->filters['device'] !== 'all') {
            $query->where('device', $this->filters['device']);
        }

        // Country filter
        if ($this->filters['country'] !== 'all') {
            $query->where('country', $this->filters['country']);
        }

        // Position range filter
        if ($this->filters['position_range'] !== 'all') {
            switch ($this->filters['position_range']) {
                case 'top3':
                    $query->where('position', '<=', 3);
                    break;
                case 'top10':
                    $query->where('position', '<=', 10);
                    break;
                case '11-20':
                    $query->whereBetween('position', [10.01, 20]);
                    break;
                case '21-50':
                    $query->whereBetween('position', [20.01, 50]);
                    break;
                case '50+':
                    $query->where('position', '>', 50);
                    break;
            }
        }

        return $query;
    }
}
