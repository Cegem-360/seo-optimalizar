<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;

enum NavigationGroups: string implements HasIcon, HasLabel
{
    case SeoManagement = 'SEO Management';
    case Reports = 'Reports';
    case Settings = 'Settings';
    case Analytics = 'Analytics';
    case SeoTools = 'SEO Tools';

    public function getLabel(): string
    {
        return match ($this) {
            self::SeoManagement => 'SEO Management',
            self::Reports => 'Reports',
            self::Settings => 'Settings',
            self::Analytics => 'Analytics',
            self::SeoTools => 'SEO Tools',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::SeoManagement => Heroicon::MagnifyingGlass->getIconForSize(IconSize::Small),
            self::Reports => Heroicon::DocumentChartBar->getIconForSize(IconSize::Small),
            self::Settings => Heroicon::Cog6Tooth->getIconForSize(IconSize::Small),
            self::Analytics => Heroicon::ChartBar->getIconForSize(IconSize::Small),
            self::SeoTools => Heroicon::WrenchScrewdriver->getIconForSize(IconSize::Small),
        };
    }
}
