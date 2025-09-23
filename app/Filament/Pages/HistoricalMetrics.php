<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Keyword;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class HistoricalMetrics extends Page
{
    protected string $view = 'filament.pages.historical-metrics';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ChartBar;

    protected static ?string $navigationLabel = 'Történeti Metrikák';

    protected static ?string $title = 'Kulcsszavak Történeti Metrikái';

    protected static ?int $navigationSort = 90;

    public ?array $selectedKeyword = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public array $chartData = [];

    public array $keywordsList = [];

    public function mount(): void
    {
        $this->keywordsList = Keyword::query()->whereNotNull('monthly_search_volumes')
            ->whereNotNull('historical_metrics_updated_at')
            ->with('project')
            ->get()
            ->mapWithKeys(function (Keyword $keyword): array {
                $projectName = $keyword->project->name ?? 'Nincs projekt';

                return [$keyword->id => $keyword->keyword . ' (' . $projectName . ')'];
            })
            ->toArray();

        $this->dateFrom = Carbon::now()->subMonths(12)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filterData')
                ->label('Szűrés')
                ->icon(Heroicon::AdjustmentsHorizontal)
                ->color('primary')
                ->schema([
                    Select::make('keyword_id')
                        ->label('Kulcsszó')
                        ->options($this->keywordsList)
                        ->searchable()
                        ->placeholder('Válassz kulcsszót'),
                    DatePicker::make('date_from')
                        ->label('Kezdő dátum')
                        ->default($this->dateFrom),
                    DatePicker::make('date_to')
                        ->label('Záró dátum')
                        ->default($this->dateTo),
                ])
                ->action(function (array $data): void {
                    if ($data['keyword_id']) {
                        $this->selectedKeyword = Keyword::with('project')
                            ->find($data['keyword_id'])
                            ->toArray();
                        $this->loadChartData();
                    }

                    $this->dateFrom = $data['date_from'] ?? $this->dateFrom;
                    $this->dateTo = $data['date_to'] ?? $this->dateTo;
                })
                ->modalHeading('Történeti adatok szűrése')
                ->modalSubmitActionLabel('Szűrés alkalmazása')
                ->modalCancelActionLabel('Mégse'),

            Action::make('exportData')
                ->label('Exportálás')
                ->icon(Heroicon::ArrowDown)
                ->color('success')
                ->disabled(empty($this->selectedKeyword))
                ->action(function (): void {
                    if ($this->selectedKeyword !== null && $this->selectedKeyword !== []) {
                        $this->exportToCSV();
                    }
                }),
        ];
    }

    protected function loadChartData(): void
    {
        if ($this->selectedKeyword === null || $this->selectedKeyword === []) {
            return;
        }

        $keyword = Keyword::query()->find($this->selectedKeyword['id']);

        if (! $keyword) {
            $this->chartData = [];

            return;
        }

        /** @var array<array{year: int, month: int, search_volume: int}>|null $monthlyData */
        $monthlyData = $keyword->monthly_search_volumes;

        if (! $monthlyData || ! is_array($monthlyData)) {
            $this->chartData = [];

            return;
        }

        // Szűrés dátum alapján ha szükséges
        $filteredData = [];
        $fromDate = Carbon::parse($this->dateFrom);
        $toDate = Carbon::parse($this->dateTo);

        foreach ($monthlyData as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (empty($entry['year'])) {
                continue;
            }

            if (empty($entry['month'])) {
                continue;
            }

            if (! isset($entry['search_volume'])) {
                continue;
            }

            $entryDate = Carbon::createFromDate($entry['year'], $entry['month'], 1);

            if ($entryDate->between($fromDate, $toDate)) {
                $filteredData[] = [
                    'date' => $entryDate->format('Y-m'),
                    'search_volume' => $entry['search_volume'],
                    'formatted_date' => $entryDate->format('Y. F'),
                ];
            }
        }

        // Rendezzük dátum szerint
        usort($filteredData, fn (array $a, array $b): int => strcmp($a['date'], $b['date']));

        $this->chartData = $filteredData;
    }

    protected function exportToCSV(): void
    {
        if ($this->selectedKeyword === null || $this->selectedKeyword === [] || $this->chartData === []) {
            return;
        }

        $filename = 'historical_metrics_' . $this->selectedKeyword['keyword'] . '_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function (): void {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM hozzáadása Excel számára
            fwrite($file, "\xEF\xBB\xBF");

            // Fejléc
            fputcsv($file, ['Dátum', 'Keresési volumen', 'Kulcsszó', 'Projekt'], ';');

            // Adatok
            foreach ($this->chartData as $row) {
                fputcsv($file, [
                    $row['formatted_date'],
                    $row['search_volume'],
                    $this->selectedKeyword['keyword'],
                    $this->selectedKeyword['project']['name'],
                ], ';');
            }

            fclose($file);
        };

        response()->stream($callback, 200, $headers);
    }
}
