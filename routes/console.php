<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('gsc:sync --all')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function (): void {
        info('Google Search Console sync completed successfully');
    })
    ->onFailure(function (): void {
        error('Google Search Console sync failed');
    });

Schedule::command('seo:check-positions')
    ->twiceDaily(9, 21)
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function (): void {
        info('Keyword position check completed successfully');
    })
    ->onFailure(function (): void {
        info('Keyword position check failed');
    });

Schedule::command('seo:send-weekly-summary')
    ->weeklyOn(1, '09:00') // Every Monday at 9:00 AM
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function (): void {
        info('Weekly SEO summary sent successfully');
    })
    ->onFailure(function (): void {
        info('Weekly SEO summary failed');
    });
