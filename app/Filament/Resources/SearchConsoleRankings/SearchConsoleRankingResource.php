<?php

namespace App\Filament\Resources\SearchConsoleRankings;

use App\Enums\NavigationGroups;
use App\Filament\Resources\SearchConsoleRankings\Pages\CreateSearchConsoleRanking;
use App\Filament\Resources\SearchConsoleRankings\Pages\EditSearchConsoleRanking;
use App\Filament\Resources\SearchConsoleRankings\Pages\ListSearchConsoleRankings;
use App\Filament\Resources\SearchConsoleRankings\Pages\SearchConsoleRankingsDashboard;
use App\Filament\Resources\SearchConsoleRankings\Schemas\SearchConsoleRankingForm;
use App\Filament\Resources\SearchConsoleRankings\Tables\SearchConsoleRankingsTable;
use App\Filament\Resources\SearchConsoleRankings\Widgets\ClicksPerformanceWidget;
use App\Filament\Resources\SearchConsoleRankings\Widgets\PositionDistributionWidget;
use App\Filament\Resources\SearchConsoleRankings\Widgets\SearchConsoleOverviewWidget;
use App\Models\SearchConsoleRanking;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class SearchConsoleRankingResource extends Resource
{
    protected static ?string $model = SearchConsoleRanking::class;

    protected static ?string $tenantOwnershipRelationshipName = 'project';

    protected static ?string $navigationLabel = 'Search Console Rankings';

    protected static ?string $pluralModelLabel = 'Search Console Rankings';

    protected static ?string $modelLabel = 'Search Console Ranking';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroups::SeoManagement;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SearchConsoleRankingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SearchConsoleRankingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSearchConsoleRankings::route('/'),
            'dashboard' => SearchConsoleRankingsDashboard::route('/dashboard'),
            'create' => CreateSearchConsoleRanking::route('/create'),
            'edit' => EditSearchConsoleRanking::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            SearchConsoleOverviewWidget::class,
            PositionDistributionWidget::class,
            ClicksPerformanceWidget::class,
        ];
    }
}
