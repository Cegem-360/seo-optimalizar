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

// PageSpeed Monitoring - Mobile analysis daily at 8:00 AM
Schedule::command('seo:pagespeed-monitor --strategy=mobile')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function (): void {
        info('Daily PageSpeed mobile monitoring completed successfully');
    })
    ->onFailure(function (): void {
        error('Daily PageSpeed mobile monitoring failed');
    });

// PageSpeed Monitoring - Desktop analysis daily at 2:00 PM
Schedule::command('seo:pagespeed-monitor --strategy=desktop')
    ->dailyAt('14:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function (): void {
        info('Daily PageSpeed desktop monitoring completed successfully');
    })
    ->onFailure(function (): void {
        error('Daily PageSpeed desktop monitoring failed');
    });

// PageSpeed Monitoring - Weekly comprehensive analysis (both mobile & desktop)
Schedule::command('seo:pagespeed-monitor --strategy=both --force')
    ->weeklyOn(0, '10:00') // Every Sunday at 10:00 AM
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function (): void {
        info('Weekly comprehensive PageSpeed monitoring completed successfully');
    })
    ->onFailure(function (): void {
        error('Weekly comprehensive PageSpeed monitoring failed');
    });
