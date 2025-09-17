<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $website_analysis_id
 * @property string $section_type
 * @property string $section_name
 * @property int|null $score
 * @property string|null $status
 * @property array<array-key, mixed>|null $findings
 * @property array<array-key, mixed>|null $recommendations
 * @property array<array-key, mixed>|null $data
 * @property string|null $summary
 * @property int $priority
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\WebsiteAnalysis $websiteAnalysis
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereFindings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereRecommendations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereSectionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereSectionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnalysisSection whereWebsiteAnalysisId($value)
 */
	class AnalysisSection extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static Builder<static>|ApiConfig newModelQuery()
 * @method static Builder<static>|ApiConfig newQuery()
 * @method static Builder<static>|ApiConfig query()
 * @mixin \Eloquent
 */
	class ApiConfig extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read Project|null $project
 * @property array<string, mixed> $credentials
 * @method static ApiCredentialFactory factory($count = null, $state = [])
 * @method static Builder<static>|ApiCredential newModelQuery()
 * @method static Builder<static>|ApiCredential newQuery()
 * @method static Builder<static>|ApiCredential query()
 * @mixin Model
 * @property int $id
 * @property int $project_id
 * @property string $service
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property string|null $service_account_file
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $service_account_json
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiCredential whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiCredential whereCredentials($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiCredential whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiCredential whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiCredential whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiCredential whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiCredential whereService($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiCredential whereServiceAccountFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiCredential whereUpdatedAt($value)
 */
	class ApiCredential extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $url
 * @property string $domain
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CompetitorRanking> $competitorRankings
 * @property-read int|null $competitor_rankings_count
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor active()
 * @method static \Database\Factories\CompetitorFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competitor whereUrl($value)
 */
	class Competitor extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $keyword_id
 * @property int $project_id
 * @property string $competitor_domain
 * @property string|null $competitor_url
 * @property int $position
 * @property int|null $domain_authority
 * @property int|null $page_authority
 * @property int|null $backlinks_count
 * @property int|null $content_length
 * @property int|null $keyword_density
 * @property bool $has_schema_markup
 * @property bool $has_featured_snippet
 * @property float|null $page_speed_score
 * @property bool $is_mobile_friendly
 * @property bool $has_ssl
 * @property string|null $title_tag
 * @property string|null $meta_description
 * @property array<array-key, mixed>|null $headers_structure
 * @property \Illuminate\Support\Carbon|null $analyzed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $ai_discovered
 * @property string|null $competitor_type
 * @property int|float $strength_score
 * @property string|null $relevance_reason
 * @property array<array-key, mixed>|null $main_advantages
 * @property string|null $estimated_traffic
 * @property string|null $content_focus
 * @property array<array-key, mixed>|null $competitor_strengths
 * @property array<array-key, mixed>|null $competitor_weaknesses
 * @property array<array-key, mixed>|null $project_strengths
 * @property array<array-key, mixed>|null $project_weaknesses
 * @property array<array-key, mixed>|null $opportunities
 * @property array<array-key, mixed>|null $threats
 * @property array<array-key, mixed>|null $action_items
 * @property int|null $competitive_advantage_score
 * @property string|null $ai_analysis_summary
 * @property-read mixed $action_items_by_priority
 * @property-read string $competitor_type_color
 * @property-read bool $is_strong_competitor
 * @property-read \App\Models\Keyword $keyword
 * @property-read string $position_badge_color
 * @property-read \App\Models\Project $project
 * @property-read string $traffic_color
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereActionItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereAiAnalysisSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereAiDiscovered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereAnalyzedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereBacklinksCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereCompetitiveAdvantageScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereCompetitorDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereCompetitorStrengths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereCompetitorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereCompetitorUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereCompetitorWeaknesses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereContentFocus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereContentLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereDomainAuthority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereEstimatedTraffic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereHasFeaturedSnippet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereHasSchemaMarkup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereHasSsl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereHeadersStructure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereIsMobileFriendly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereKeywordDensity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereKeywordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereMainAdvantages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereOpportunities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis wherePageAuthority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis wherePageSpeedScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereProjectStrengths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereProjectWeaknesses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereRelevanceReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereStrengthScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereThreats($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereTitleTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorAnalysis whereUpdatedAt($value)
 */
	class CompetitorAnalysis extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $competitor_id
 * @property int $keyword_id
 * @property int|null $position
 * @property int|null $previous_position
 * @property string|null $url
 * @property bool $featured_snippet
 * @property array<array-key, mixed>|null $serp_features
 * @property \Illuminate\Support\Carbon $checked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Competitor $competitor
 * @property-read \App\Models\Keyword $keyword
 * @property-read int|null $position_change
 * @method static \Database\Factories\CompetitorRankingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking recentlyChecked(int $days = 7)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking topTen()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking whereCompetitorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking whereFeaturedSnippet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking whereKeywordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking wherePreviousPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking whereSerpFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompetitorRanking whereUrl($value)
 */
	class CompetitorRanking extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_id
 * @property string $keyword
 * @property string|null $category
 * @property string $priority
 * @property string $geo_target
 * @property string $language
 * @property int|null $search_volume
 * @property int|null $difficulty_score
 * @property string|null $intent_type
 * @property string|null $notes
 * @property int|null $competition_index
 * @property numeric|null $low_top_of_page_bid
 * @property numeric|null $high_top_of_page_bid
 * @property array<array-key, mixed>|null $monthly_search_volumes
 * @property \Illuminate\Support\Carbon|null $historical_metrics_updated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CompetitorAnalysis> $competitorAnalyses
 * @property-read int|null $competitor_analyses_count
 * @property-read \App\Models\Ranking|null $latestRanking
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PageSpeedAnalysis> $pageSpeedAnalyses
 * @property-read int|null $page_speed_analyses_count
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ranking> $rankings
 * @property-read int|null $rankings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeoAnalysis> $seoAnalyses
 * @property-read int|null $seo_analyses_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword byCategory(string $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword byIntentType(string $intentType)
 * @method static \Database\Factories\KeywordFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword highPriority()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereCompetitionIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereDifficultyScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereGeoTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereHighTopOfPageBid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereHistoricalMetricsUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereIntentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereKeyword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereLowTopOfPageBid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereMonthlySearchVolumes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereSearchVolume($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyword whereUpdatedAt($value)
 */
	class Keyword extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $project_id
 * @property bool $email_ranking_changes
 * @property bool $email_top3_achievements
 * @property bool $email_first_page_entries
 * @property bool $email_significant_drops
 * @property bool $email_weekly_summary
 * @property bool $app_ranking_changes
 * @property bool $app_top3_achievements
 * @property bool $app_first_page_entries
 * @property bool $app_significant_drops
 * @property int $significant_change_threshold
 * @property bool $only_significant_changes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereAppFirstPageEntries($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereAppRankingChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereAppSignificantDrops($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereAppTop3Achievements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereEmailFirstPageEntries($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereEmailRankingChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereEmailSignificantDrops($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereEmailTop3Achievements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereEmailWeeklySummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereOnlySignificantChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereSignificantChangeThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereUserId($value)
 */
	class NotificationPreference extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_id
 * @property int|null $keyword_id
 * @property string $tested_url
 * @property string $device_type
 * @property float|null $lcp
 * @property float|null $fid
 * @property float|null $cls
 * @property float|null $fcp
 * @property float|null $inp
 * @property float|null $ttfb
 * @property int|null $performance_score
 * @property int|null $accessibility_score
 * @property int|null $best_practices_score
 * @property int|null $seo_score
 * @property int|null $total_page_size
 * @property int|null $total_requests
 * @property float|null $load_time
 * @property array<array-key, mixed>|null $resource_breakdown
 * @property array<array-key, mixed>|null $third_party_resources
 * @property array<array-key, mixed>|null $opportunities
 * @property array<array-key, mixed>|null $diagnostics
 * @property int|null $images_count
 * @property int|null $unoptimized_images
 * @property int|null $images_without_alt
 * @property int|null $render_blocking_resources
 * @property int|null $unused_css_bytes
 * @property int|null $unused_js_bytes
 * @property string $analysis_source
 * @property \Illuminate\Support\Carbon $analyzed_at
 * @property array<array-key, mixed>|null $raw_response
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $core_web_vitals_status
 * @property-read string $formatted_page_size
 * @property-read \App\Models\Keyword|null $keyword
 * @property-read string $performance_color
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereAccessibilityScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereAnalysisSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereAnalyzedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereBestPracticesScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereCls($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereDiagnostics($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereFcp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereFid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereImagesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereImagesWithoutAlt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereInp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereKeywordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereLcp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereLoadTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereOpportunities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis wherePerformanceScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereRawResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereRenderBlockingResources($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereResourceBreakdown($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereSeoScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereTestedUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereThirdPartyResources($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereTotalPageSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereTotalRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereTtfb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereUnoptimizedImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereUnusedCssBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereUnusedJsBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedAnalysis whereUpdatedAt($value)
 */
	class PageSpeedAnalysis extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_id
 * @property string $url
 * @property string $strategy
 * @property int|null $performance_score
 * @property int|null $accessibility_score
 * @property int|null $best_practices_score
 * @property int|null $seo_score
 * @property numeric|null $lcp_value
 * @property string|null $lcp_display
 * @property numeric|null $lcp_score
 * @property numeric|null $fcp_value
 * @property string|null $fcp_display
 * @property numeric|null $fcp_score
 * @property numeric|null $cls_value
 * @property string|null $cls_display
 * @property numeric|null $cls_score
 * @property numeric|null $speed_index_value
 * @property string|null $speed_index_display
 * @property numeric|null $speed_index_score
 * @property array<array-key, mixed>|null $raw_data
 * @property \Illuminate\Support\Carbon $analyzed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float|null $overall_score
 * @property-read string $performance_grade
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult forProject(int $projectId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult recent(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult strategy(string $strategy)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereAccessibilityScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereAnalyzedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereBestPracticesScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereClsDisplay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereClsScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereClsValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereFcpDisplay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereFcpScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereFcpValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereLcpDisplay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereLcpScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereLcpValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult wherePerformanceScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereRawData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereSeoScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereSpeedIndexDisplay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereSpeedIndexScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereSpeedIndexValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereStrategy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PageSpeedResult whereUrl($value)
 */
	class PageSpeedResult extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApiCredential> $apiCredentials
 * @property-read int|null $api_credentials_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Competitor> $competitors
 * @property-read int|null $competitors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Keyword> $keywords
 * @property-read int|null $keywords_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NotificationPreference> $notificationPreferences
 * @property-read int|null $notification_preferences_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PageSpeedResult> $pageSpeedResults
 * @property-read int|null $page_speed_results_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ranking> $rankings
 * @property-read int|null $rankings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Report> $reports
 * @property-read int|null $reports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project active()
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withKeywordCount()
 */
	class Project extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $keyword_id
 * @property int|null $position
 * @property int|null $previous_position
 * @property string|null $url
 * @property bool $featured_snippet
 * @property array<array-key, mixed>|null $serp_features
 * @property \Illuminate\Support\Carbon $checked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Keyword $keyword
 * @property-read int|null $position_change
 * @property-read string $position_trend
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking declined()
 * @method static \Database\Factories\RankingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking improved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking recentlyChecked(int $days = 7)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking topTen()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking topThree()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking whereFeaturedSnippet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking whereKeywordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking wherePreviousPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking whereSerpFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ranking whereUrl($value)
 */
	class Ranking extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string $type
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property array<array-key, mixed>|null $data
 * @property string|null $file_path
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $formatted_period
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report completed()
 * @method static \Database\Factories\ReportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report recent(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereGeneratedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereUpdatedAt($value)
 */
	class Report extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $keyword_id
 * @property int $project_id
 * @property string|null $competition_level
 * @property string|null $search_intent
 * @property array<array-key, mixed>|null $dominant_content_types
 * @property array<array-key, mixed>|null $opportunities
 * @property array<array-key, mixed>|null $challenges
 * @property array<array-key, mixed>|null $optimization_tips
 * @property string|null $summary
 * @property string|null $position_rating
 * @property int|null $current_position
 * @property int|null $target_position
 * @property string|null $estimated_timeframe
 * @property array<array-key, mixed>|null $main_competitors
 * @property array<array-key, mixed>|null $competitor_advantages
 * @property array<array-key, mixed>|null $improvement_areas
 * @property array<array-key, mixed>|null $quick_wins
 * @property string|null $detailed_analysis
 * @property array<array-key, mixed>|null $raw_response
 * @property string $analysis_source
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $competition_level_color
 * @property-read \App\Models\Keyword $keyword
 * @property-read string $position_rating_color
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereAnalysisSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereChallenges($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereCompetitionLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereCompetitorAdvantages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereCurrentPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereDetailedAnalysis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereDominantContentTypes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereEstimatedTimeframe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereImprovementAreas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereKeywordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereMainCompetitors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereOpportunities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereOptimizationTips($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis wherePositionRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereQuickWins($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereRawResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereSearchIntent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereTargetPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoAnalysis whereUpdatedAt($value)
 */
	class SeoAnalysis extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property int|null $latest_project_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project|null $latestProject
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NotificationPreference> $notificationPreferences
 * @property-read int|null $notification_preferences_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLatestProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent implements \Filament\Models\Contracts\FilamentUser, \Filament\Models\Contracts\HasTenants {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_id
 * @property string $url
 * @property string $analysis_type
 * @property string $ai_provider
 * @property string|null $ai_model
 * @property array<array-key, mixed>|null $request_params
 * @property string|null $raw_response
 * @property int|null $overall_score
 * @property array<array-key, mixed>|null $scores
 * @property array<array-key, mixed>|null $metadata
 * @property string $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $analyzed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AnalysisSection> $sections
 * @property-read int|null $sections_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereAiModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereAiProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereAnalysisType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereAnalyzedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereOverallScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereRawResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereRequestParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereScores($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteAnalysis whereUrl($value)
 */
	class WebsiteAnalysis extends \Eloquent {}
}

