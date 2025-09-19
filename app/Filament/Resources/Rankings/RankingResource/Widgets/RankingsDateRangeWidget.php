<?php

namespace App\Filament\Resources\Rankings\RankingResource\Widgets;

use App\Models\Project;
use App\Models\Ranking;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class RankingsDateRangeWidget extends Widget
{
    protected static string $view = 'filament.resources.rankings.widgets.date-range-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -2;

    public function getDateRangeData(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [
                'hasData' => false,
            ];
        }

        // Get all rankings for the project
        $allRankings = Ranking::query()
            ->whereHas('keyword', function ($query) use ($project): void {
                $query->where('project_id', $project->id);
            });

        // Calculate date ranges
        $earliestRanking = (clone $allRankings)->oldest('checked_at')->first();
        $latestRanking = (clone $allRankings)->latest('checked_at')->first();

        if (!$earliestRanking || !$latestRanking) {
            return [
                'hasData' => false,
            ];
        }

        $startDate = Carbon::parse($earliestRanking->checked_at);
        $endDate = Carbon::parse($latestRanking->checked_at);

        // Get statistics for different periods
        $todayCount = (clone $allRankings)->whereDate('checked_at', Carbon::today())->count();
        $yesterdayCount = (clone $allRankings)->whereDate('checked_at', Carbon::yesterday())->count();
        $weekCount = (clone $allRankings)->whereBetween('checked_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ])->count();
        $monthCount = (clone $allRankings)->whereMonth('checked_at', Carbon::now()->month)
            ->whereYear('checked_at', Carbon::now()->year)->count();
        $totalCount = (clone $allRankings)->count();

        // Get unique dates with data
        $uniqueDates = (clone $allRankings)
            ->selectRaw('DATE(checked_at) as date')
            ->distinct()
            ->pluck('date')
            ->count();

        // Calculate daily average
        $daysDifference = $startDate->diffInDays($endDate) + 1;
        $dailyAverage = $daysDifference > 0 ? round($totalCount / $daysDifference, 1) : 0;

        return [
            'hasData' => true,
            'startDate' => $startDate->format('M d, Y'),
            'endDate' => $endDate->format('M d, Y'),
            'startTime' => $startDate->format('H:i'),
            'endTime' => $endDate->format('H:i'),
            'daysSpan' => $daysDifference,
            'uniqueDates' => $uniqueDates,
            'statistics' => [
                'today' => $todayCount,
                'yesterday' => $yesterdayCount,
                'thisWeek' => $weekCount,
                'thisMonth' => $monthCount,
                'total' => $totalCount,
                'dailyAverage' => $dailyAverage,
            ],
            'coverage' => [
                'datesWithData' => $uniqueDates,
                'totalDays' => $daysDifference,
                'coveragePercent' => $daysDifference > 0 ? round(($uniqueDates / $daysDifference) * 100, 1) : 0,
            ],
        ];
    }
}
