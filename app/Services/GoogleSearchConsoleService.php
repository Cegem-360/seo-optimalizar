<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\Project;
use App\Models\User;
use App\Notifications\RankingChangeNotification;
use Carbon\Carbon;
use Exception;
use Google\Client as GoogleClient;
use Google\Service\SearchConsole;
use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Log;

class GoogleSearchConsoleService
{
    /**
     * @var Repository
     */
    public $repository;

    private readonly GoogleClient $googleClient;

    private readonly SearchConsole $searchConsole;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->googleClient = new GoogleClient();
        $this->googleClient->setApplicationName('SEO Monitor');
        $this->googleClient->setScopes([SearchConsole::WEBMASTERS_READONLY]);

        // Try Service Account credentials first
        if ($credentialsPath = $repository->get('services.google.credentials_path')) {
            $fullPath = base_path($credentialsPath);
            if (file_exists($fullPath)) {
                $this->googleClient->setAuthConfig($fullPath);
                $this->googleClient->useApplicationDefaultCredentials();

                // If using Google Workspace domain-wide delegation
                if ($subject = $repository->get('services.google.workspace_subject')) {
                    $this->googleClient->setSubject($subject);
                    // Log::info('Using Google Workspace delegation for: ' . $subject);
                }
            } else {
                Log::warning('Google Service Account credentials file not found: ' . $fullPath);
            }
        }
        // Fall back to OAuth if configured
        elseif ($clientId = $repository->get('services.google.client_id')) {
            $this->googleClient->setClientId($clientId);
            $this->googleClient->setClientSecret($repository->get('services.google.client_secret'));
            $this->googleClient->setRedirectUri($repository->get('services.google.redirect_uri'));
            // Note: OAuth flow needs to be handled separately
        }

        $this->searchConsole = new SearchConsole($this->googleClient);
    }

    /**
     * Get performance data from Search Console
     */
    public function getPerformanceData(Project $project, array $keywords = [], ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        try {
            $startDate ??= Carbon::now()->subDays(30);
            $endDate ??= Carbon::now()->subDay();

            $searchAnalyticsQueryRequest = new SearchAnalyticsQueryRequest();
            $searchAnalyticsQueryRequest->setStartDate($startDate->format('Y-m-d'));
            $searchAnalyticsQueryRequest->setEndDate($endDate->format('Y-m-d'));
            $searchAnalyticsQueryRequest->setDimensions(['query', 'page']);
            $searchAnalyticsQueryRequest->setRowLimit(1000);

            if ($keywords !== []) {
                $filters = [];
                foreach ($keywords as $keyword) {
                    $filters[] = [
                        'dimension' => 'query',
                        'operator' => 'equals',
                        'expression' => $keyword,
                    ];
                }

                $searchAnalyticsQueryRequest->setDimensionFilterGroups([
                    ['filters' => $filters],
                ]);
            }

            $response = $this->searchConsole->searchanalytics->query($project->url, $searchAnalyticsQueryRequest);

            return $this->processSearchConsoleResponse($response, $project);
        } catch (Exception $exception) {
            Log::error('Google Search Console API Error: ' . $exception->getMessage());

            return [];
        }
    }

    /**
     * Process Search Console API response
     */
    private function processSearchConsoleResponse($response, Project $project): array
    {
        $data = [];

        if ($response->getRows()) {
            foreach ($response->getRows() as $row) {
                $query = $row->getKeys()[0];
                $page = $row->getKeys()[1] ?? null;

                $data[] = [
                    'keyword' => $query,
                    'url' => $page,
                    'clicks' => $row->getClicks() ?? 0,
                    'impressions' => $row->getImpressions() ?? 0,
                    'ctr' => $row->getCtr() ?? 0,
                    'position' => round($row->getPosition() ?? 0, 1),
                    'project_id' => $project->id,
                ];
            }
        }

        return $data;
    }

    /**
     * Import performance data and update rankings
     */
    public function importAndUpdateRankings(Project $project): int
    {
        $performanceData = $this->getPerformanceData($project);
        $importedCount = 0;

        foreach ($performanceData as $data) {
            // Find or create keyword
            /** @var Keyword $keyword */
            $keyword = Keyword::query()->firstOrCreate([
                'project_id' => $project->id,
                'keyword' => $data['keyword'],
            ], [
                'category' => 'Search Console Import',
                'priority' => 'medium',
                'search_volume' => null,
                'difficulty_score' => null,
            ]);

            // Get previous ranking for comparison
            /** @var SearchConsoleRanking|null $previousRanking */
            $previousRanking = $project->searchConsoleRankings()->where('query', $keyword->keyword)->latest('date_to')->first();

            // Create new ranking entry
            $ranking = SearchConsoleRanking::query()->create([
                'project_id' => $project->id,
                'query' => $keyword->keyword,
                'page' => $data['url'],
                'position' => $data['position'],
                'previous_position' => $previousRanking?->position,
                'clicks' => $data['clicks'] ?? 0,
                'impressions' => $data['impressions'] ?? 0,
                'ctr' => $data['ctr'] ?? 0,
                'date_from' => now()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
                'days_count' => 1,
                'device' => 'desktop',
                'country' => 'hun',
                'fetched_at' => now(),
            ]);

            // Check for significant changes and send notifications
            $ranking->loadMissing(['project']);
            $this->checkForSignificantChanges($ranking);

            $importedCount++;
        }

        return $importedCount;
    }

    /**
     * Get sites from Search Console
     */
    public function getSites(): array
    {
        try {
            $sites = $this->searchConsole->sites->listSites();

            return $sites->getSiteEntry() ?? [];
        } catch (Exception $exception) {
            Log::error('Error fetching Search Console sites: ' . $exception->getMessage());

            return [];
        }
    }

    /**
     * Check if credentials are configured
     */
    public function hasCredentials(): bool
    {
        // Check for Service Account credentials
        if ($credentialsPath = $this->repository->get('services.google.credentials_path')) {
            $fullPath = base_path($credentialsPath);
            if (file_exists($fullPath)) {
                return true;
            }
        }

        // Check for OAuth credentials
        return $this->repository->get('services.google.client_id') !== null &&
               $this->repository->get('services.google.client_secret') !== null;
    }

    /**
     * Check if using Service Account authentication
     */
    public function isUsingServiceAccount(): bool
    {
        if ($credentialsPath = $this->repository->get('services.google.credentials_path')) {
            $fullPath = base_path($credentialsPath);

            return file_exists($fullPath);
        }

        return false;
    }

    private function checkForSignificantChanges(SearchConsoleRanking $searchConsoleRanking): void
    {
        $previousPosition = $searchConsoleRanking->previous_position;
        $currentPosition = $searchConsoleRanking->position;

        // No previous position, this is new
        if (! $previousPosition) {
            // Send notification only if it's a good position
            if ($currentPosition <= 10) {
                $this->sendNotification($searchConsoleRanking, $currentPosition <= 3 ? 'top3' : 'first_page');
            }

            return;
        }

        $change = $previousPosition - $currentPosition;

        // Check for significant improvements (position got better)
        if ($change > 0) {
            // Entered top 3
            if ($currentPosition <= 3 && $previousPosition > 3) {
                $this->sendNotification($searchConsoleRanking, 'top3');
            }
            // Entered first page
            elseif ($currentPosition <= 10 && $previousPosition > 10) {
                $this->sendNotification($searchConsoleRanking, 'first_page');
            }
            // Significant improvement (5+ positions)
            elseif ($change >= 5) {
                $this->sendNotification($searchConsoleRanking, 'significant_improvement');
            }
        }

        // Check for significant declines (position got worse)
        if ($change < 0) {
            $absoluteChange = abs($change);

            // Dropped out of first page
            if ($previousPosition <= 10 && $currentPosition > 10) {
                $this->sendNotification($searchConsoleRanking, 'dropped_out');
            }
            // Significant decline (5+ positions)
            elseif ($absoluteChange >= 5) {
                $this->sendNotification($searchConsoleRanking, 'significant_decline');
            }
        }
    }

    private function sendNotification(SearchConsoleRanking $searchConsoleRanking, string $changeType): void
    {
        try {
            $searchConsoleRanking->loadMissing(['project']);

            if (! $searchConsoleRanking->project) {
                return;
            }

            /** @var Project $project */
            $project = $searchConsoleRanking->project;

            // Get all users who have access to this project
            $users = $project->users;

            /** @var User $user */
            foreach ($users as $user) {
                $preferences = $user->getNotificationPreferencesForProject($project);

                // Check if user wants to receive this type of notification
                if ($preferences->shouldReceiveEmail($changeType) ||
                    $preferences->shouldReceiveAppNotification($changeType)) {
                    // Set notification channels based on preferences
                    $channels = [];
                    if ($preferences->shouldReceiveEmail($changeType)) {
                        $channels[] = 'mail';
                    }

                    if ($preferences->shouldReceiveAppNotification($changeType)) {
                        $channels[] = 'database';
                    }

                    $notification = new RankingChangeNotification($searchConsoleRanking, $changeType, $this->repository->get('app.url'), $channels);
                    $user->notify($notification);
                }
            }
        } catch (Exception $exception) {
            Log::error('Failed to send ranking notification: ' . $exception->getMessage());
        }
    }
}
