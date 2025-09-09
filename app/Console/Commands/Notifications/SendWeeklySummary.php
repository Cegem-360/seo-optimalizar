<?php

namespace App\Console\Commands\Notifications;

use App\Models\Project;
use App\Models\Ranking;
use App\Notifications\WeeklySummaryNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\Log;

class SendWeeklySummary extends Command
{
    protected $signature = 'seo:send-weekly-summary {--project= : Send summary for specific project ID}';

    protected $description = 'Send weekly SEO summary emails to project users';

    public function __construct(private readonly UrlGenerator $urlGenerator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Generating and sending weekly SEO summaries...');

        $projects = $this->option('project')
            ? Project::query()->where('id', $this->option('project'))->get()
            : Project::all();

        if ($projects->isEmpty()) {
            $this->error('No projects found to generate summaries for.');

            return self::FAILURE;
        }

        $totalSent = 0;
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        foreach ($projects as $project) {
            $this->info('Generating summary for project: ' . $project->name);

            try {
                $summaryData = $this->generateSummaryData($project, $weekStart, $weekEnd);

                if ($summaryData['total_keywords'] == 0) {
                    $this->warn(sprintf('No keywords found for project: %s. Skipping.', $project->name));

                    continue;
                }

                // Get all users who have access to this project
                $users = $project->users()->get();

                if ($users->isEmpty()) {
                    $this->warn(sprintf('No users found for project: %s. Skipping.', $project->name));

                    continue;
                }

                foreach ($users as $user) {
                    $user->notify(new WeeklySummaryNotification($project, $summaryData, $this->urlGenerator));
                    $totalSent++;
                }

                $this->info(sprintf('✓ Summary sent to %s users for %s', $users->count(), $project->name));
            } catch (Exception $e) {
                $this->error(sprintf('✗ Error generating summary for %s: ', $project->name) . $e->getMessage());
                Log::error(sprintf('Weekly summary generation error for project %s: ', $project->id) . $e->getMessage());
            }
        }

        $this->info('Weekly summary completed! Total summaries sent: ' . $totalSent);

        return self::SUCCESS;
    }

    private function generateSummaryData(Project $project, Carbon $weekStart, Carbon $weekEnd): array
    {
        $keywords = $project->keywords();
        $totalKeywords = $keywords->count();

        // Get latest rankings for each keyword
        $latestRankings = Ranking::query()->whereHas('keyword', function ($query) use ($project): void {
            $query->where('project_id', $project->id);
        })
            ->with(['keyword'])
            ->where('checked_at', '>=', $weekStart)
            ->where('checked_at', '<=', $weekEnd)
            ->get()
            ->groupBy('keyword_id')
            ->map(fn ($rankings) => $rankings->sortByDesc('checked_at')->first());

        $avgPosition = $latestRankings->avg('position') ?? 0;
        $top10Count = $latestRankings->where('position', '<=', 10)->count();
        $top3Count = $latestRankings->where('position', '<=', 3)->count();

        // Calculate improvements and declines
        $improvements = [];
        $declines = [];
        $opportunities = [];

        foreach ($latestRankings as $latestRanking) {
            if ($latestRanking->previous_position && $latestRanking->position !== $latestRanking->previous_position) {
                $change = $latestRanking->previous_position - $latestRanking->position;

                if ($change > 0) {
                    $improvements[] = [
                        'keyword' => $latestRanking->keyword->keyword,
                        'current_position' => $latestRanking->position,
                        'previous_position' => $latestRanking->previous_position,
                        'change' => $change,
                    ];
                } elseif ($change < 0) {
                    $declines[] = [
                        'keyword' => $latestRanking->keyword->keyword,
                        'current_position' => $latestRanking->position,
                        'previous_position' => $latestRanking->previous_position,
                        'change' => $change,
                    ];
                }
            }

            // Find optimization opportunities (positions 11-15)
            if ($latestRanking->position >= 11 && $latestRanking->position <= 15) {
                $opportunities[] = [
                    'keyword' => $latestRanking->keyword->keyword,
                    'position' => $latestRanking->position,
                ];
            }
        }

        // Sort by change magnitude
        usort($improvements, fn (array $a, array $b): int => $b['change'] <=> $a['change']);
        usort($declines, fn (array $a, array $b): int => abs($a['change']) <=> abs($b['change']));
        usort($opportunities, fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        return [
            'total_keywords' => $totalKeywords,
            'avg_position' => $avgPosition,
            'top_10_count' => $top10Count,
            'top_3_count' => $top3Count,
            'improvements' => $improvements,
            'improvements_count' => count($improvements),
            'declines' => $declines,
            'declines_count' => count($declines),
            'opportunities' => $opportunities,
            'opportunities_count' => count($opportunities),
        ];
    }
}
