<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum NavigationGroups: string implements HasIcon, HasLabel
{
    case SeoManagement = 'SEO Management';
    case Reports = 'Reports';
    case Settings = 'Settings';

    public function getLabel(): string
    {
        return match ($this) {
            self::SeoManagement => 'SEO Management',
            self::Reports => 'Reports',
            self::Settings => 'Settings',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SeoManagement => 'heroicon-o-magnifying-glass',
            self::Reports => 'heroicon-o-document-chart-bar',
            self::Settings => 'heroicon-o-cog-6-tooth',
        };
    }
}
