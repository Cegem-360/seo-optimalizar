<?php

namespace App\Filament\Resources\PageSpeedResults;

use App\Enums\NavigationGroups;
use App\Filament\Resources\PageSpeedResults\Pages\CreatePageSpeedResult;
use App\Filament\Resources\PageSpeedResults\Pages\EditPageSpeedResult;
use App\Filament\Resources\PageSpeedResults\Pages\ListPageSpeedResults;
use App\Filament\Resources\PageSpeedResults\Pages\PageSpeedDashboard;
use App\Filament\Resources\PageSpeedResults\Schemas\PageSpeedResultForm;
use App\Filament\Resources\PageSpeedResults\Tables\PageSpeedResultsTable;
use App\Filament\Resources\PageSpeedResults\Widgets\PageSpeedOverviewWidget;
use App\Filament\Resources\PageSpeedResults\Widgets\PageSpeedRecentResultsTable;
use App\Filament\Resources\PageSpeedResults\Widgets\PageSpeedTrendChart;
use App\Models\PageSpeedResult;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PageSpeedResultResource extends Resource
{
    protected static ?string $model = PageSpeedResult::class;

    /*     protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar; */

    protected static string|UnitEnum|null $navigationGroup = NavigationGroups::Analytics;

    protected static ?string $navigationLabel = 'PageSpeed Results';

    protected static ?string $modelLabel = 'PageSpeed Result';

    protected static ?string $pluralModelLabel = 'PageSpeed Results';

    protected static ?int $navigationSort = 20;

    protected static ?string $tenantOwnershipRelationshipName = 'project';

    public static function form(Schema $schema): Schema
    {
        return PageSpeedResultForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PageSpeedResultsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            PageSpeedOverviewWidget::class,
            PageSpeedTrendChart::class,
            PageSpeedRecentResultsTable::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPageSpeedResults::route('/'),
            'dashboard' => PageSpeedDashboard::route('/dashboard'),
            'create' => CreatePageSpeedResult::route('/create'),
            'edit' => EditPageSpeedResult::route('/{record}/edit'),
        ];
    }
}
