<?php

namespace App\Filament\Resources;

use App\Enums\NavigationGroups;
use App\Filament\Resources\RankingResource\Pages\RankingsDashboard;
use App\Filament\Resources\RankingResource\Widgets\RankingsDistributionChart;
use App\Filament\Resources\RankingResource\Widgets\RankingsOverviewWidget;
use App\Filament\Resources\RankingResource\Widgets\RankingsTrendChart;
use App\Filament\Resources\Rankings\Pages\CreateRanking;
use App\Filament\Resources\Rankings\Pages\EditRanking;
use App\Filament\Resources\Rankings\Pages\ListRankings;
use App\Filament\Resources\Rankings\Schemas\RankingForm;
use App\Filament\Resources\Rankings\Tables\RankingsTable;
use App\Models\Ranking;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RankingResource extends Resource
{
    protected static ?string $model = Ranking::class;

    /* protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack; */

    protected static string|UnitEnum|null $navigationGroup = NavigationGroups::SeoManagement;

    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        $builder = parent::getEloquentQuery();
        $tenant = Filament::getTenant();

        if ($tenant) {
            $builder->whereHas('keyword', function (Builder $builder) use ($tenant): void {
                $builder->where('project_id', $tenant->id);
            });
        }

        return $builder;
    }

    public static function form(Schema $schema): Schema
    {
        return RankingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RankingsTable::configure($table);
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
            RankingsOverviewWidget::class,
            RankingsTrendChart::class,
            RankingsDistributionChart::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRankings::route('/'),
            'dashboard' => RankingsDashboard::route('/dashboard'),
            'create' => CreateRanking::route('/create'),
            'edit' => EditRanking::route('/{record}/edit'),
        ];
    }
}
