<?php

declare(strict_types=1);

use App\Models\Keyword;
use App\Models\Project;
use App\Models\SerpAnalysisResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->users()->attach($this->user);
    $this->actingAs($this->user);
});

it('can save serp analysis results to database', function (): void {
    $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);

    $analysisData = [
        'project_id' => $this->project->id,
        'keyword_id' => $keyword->id,
        'search_id' => 'test-search-id',
        'organic_results' => [
            ['position' => 1, 'title' => 'Test Result', 'link' => 'https://example.com'],
        ],
        'serp_metrics' => [
            'total_results' => 1000000,
            'search_time' => '0.5 seconds',
            'device' => 'desktop',
            'location' => 'google.com',
        ],
        'analysis_data' => [
            'position_rating' => 'jó',
            'current_position' => 5,
            'main_competitors' => ['competitor1.com', 'competitor2.com'],
            'improvement_areas' => ['SEO optimization', 'Content quality'],
        ],
        'ai_analysis' => 'Detailed AI analysis text',
    ];

    $serpAnalysisResult = SerpAnalysisResult::query()->create($analysisData);

    expect($serpAnalysisResult)->toBeInstanceOf(SerpAnalysisResult::class)
        ->and($serpAnalysisResult->project_id)->toBe($this->project->id)
        ->and($serpAnalysisResult->keyword_id)->toBe($keyword->id)
        ->and($serpAnalysisResult->search_id)->toBe('test-search-id')
        ->and($serpAnalysisResult->organic_results)->toBeArray()
        ->and($serpAnalysisResult->serp_metrics)->toBeArray()
        ->and($serpAnalysisResult->analysis_data)->toBeArray()
        ->and($serpAnalysisResult->ai_analysis)->toBe('Detailed AI analysis text');

    $this->assertDatabaseHas('serp_analysis_results', [
        'project_id' => $this->project->id,
        'keyword_id' => $keyword->id,
        'search_id' => 'test-search-id',
    ]);
});

it('has working relationships', function (): void {
    $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);
    $result = SerpAnalysisResult::factory()->create([
        'project_id' => $this->project->id,
        'keyword_id' => $keyword->id,
    ]);

    expect($result->project)->toBeInstanceOf(Project::class)
        ->and($result->project->id)->toBe($this->project->id)
        ->and($result->keyword)->toBeInstanceOf(Keyword::class)
        ->and($result->keyword->id)->toBe($keyword->id);
});

it('casts json fields correctly', function (): void {
    $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);
    $result = SerpAnalysisResult::factory()->create([
        'project_id' => $this->project->id,
        'keyword_id' => $keyword->id,
    ]);

    expect($result->organic_results)->toBeArray()
        ->and($result->serp_metrics)->toBeArray()
        ->and($result->analysis_data)->toBeArray();
});

it('can store multiple analysis results for same keyword', function (): void {
    $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);

    $result1 = SerpAnalysisResult::factory()->create([
        'project_id' => $this->project->id,
        'keyword_id' => $keyword->id,
    ]);

    $result2 = SerpAnalysisResult::factory()->create([
        'project_id' => $this->project->id,
        'keyword_id' => $keyword->id,
    ]);

    expect($result1->id)->not->toBe($result2->id)
        ->and(SerpAnalysisResult::query()->where('keyword_id', $keyword->id)->count())->toBe(2);
});

it('deletes analysis results when project is deleted', function (): void {
    $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);
    $result = SerpAnalysisResult::factory()->create([
        'project_id' => $this->project->id,
        'keyword_id' => $keyword->id,
    ]);

    $this->assertDatabaseHas('serp_analysis_results', ['id' => $result->id]);

    $this->project->delete();

    $this->assertDatabaseMissing('serp_analysis_results', ['id' => $result->id]);
});

it('deletes analysis results when keyword is deleted', function (): void {
    $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);
    $result = SerpAnalysisResult::factory()->create([
        'project_id' => $this->project->id,
        'keyword_id' => $keyword->id,
    ]);

    $this->assertDatabaseHas('serp_analysis_results', ['id' => $result->id]);

    $keyword->delete();

    $this->assertDatabaseMissing('serp_analysis_results', ['id' => $result->id]);
});

it('stores complex nested json data correctly', function (): void {
    $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);

    $complexData = [
        'project_id' => $this->project->id,
        'keyword_id' => $keyword->id,
        'organic_results' => [
            [
                'position' => 1,
                'title' => 'First Result',
                'link' => 'https://first.com',
                'snippet' => 'First snippet text',
                'sitelinks' => [
                    ['title' => 'Subpage 1', 'link' => 'https://first.com/sub1'],
                    ['title' => 'Subpage 2', 'link' => 'https://first.com/sub2'],
                ],
            ],
            [
                'position' => 2,
                'title' => 'Second Result',
                'link' => 'https://second.com',
                'rich_snippet' => [
                    'rating' => 4.5,
                    'reviews' => 123,
                ],
            ],
        ],
        'serp_metrics' => [
            'total_results' => 2500000,
            'search_time' => '0.32 seconds',
            'device' => 'mobile',
            'location' => 'google.hu',
            'features' => ['featured_snippet', 'knowledge_panel', 'people_also_ask'],
        ],
        'analysis_data' => [
            'position_rating' => 'közepes',
            'current_position' => 15,
            'main_competitors' => ['comp1.com', 'comp2.com', 'comp3.com'],
            'competitor_advantages' => [
                'Better content structure',
                'More backlinks',
                'Faster page speed',
            ],
            'improvement_areas' => [
                'Optimize meta descriptions',
                'Add schema markup',
                'Improve internal linking',
            ],
            'target_position' => 5,
            'estimated_timeframe' => '2-3 months',
            'quick_wins' => [
                'Fix broken links',
                'Update title tags',
            ],
        ],
        'ai_analysis' => 'Comprehensive analysis of SERP positioning...',
    ];

    $serpAnalysisResult = SerpAnalysisResult::query()->create($complexData);
    $serpAnalysisResult->refresh();

    expect($serpAnalysisResult->organic_results)->toHaveCount(2)
        ->and($serpAnalysisResult->organic_results[0]['sitelinks'])->toHaveCount(2)
        ->and($serpAnalysisResult->organic_results[1]['rich_snippet']['rating'])->toBe(4.5)
        ->and($serpAnalysisResult->serp_metrics['features'])->toContain('featured_snippet')
        ->and($serpAnalysisResult->analysis_data['main_competitors'])->toHaveCount(3)
        ->and($serpAnalysisResult->analysis_data['target_position'])->toBe(5);
});
