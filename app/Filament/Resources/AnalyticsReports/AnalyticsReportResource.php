<?php

namespace App\Filament\Resources\AnalyticsReports;

use App\Filament\Resources\AnalyticsReports\Pages\CreateAnalyticsReport;
use App\Filament\Resources\AnalyticsReports\Pages\EditAnalyticsReport;
use App\Filament\Resources\AnalyticsReports\Pages\ListAnalyticsReports;
use App\Filament\Resources\AnalyticsReports\Pages\ViewAnalyticsReport;
use App\Filament\Resources\AnalyticsReports\Schemas\AnalyticsReportForm;
use App\Filament\Resources\AnalyticsReports\Tables\AnalyticsReportsTable;
use App\Models\AnalyticsReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AnalyticsReportResource extends Resource
{
    protected static ?string $model = AnalyticsReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlineChartBar;

    protected static ?string $navigationLabel = 'Analytics Reports';

    protected static ?string $modelLabel = 'Analytics Report';

    protected static ?string $pluralModelLabel = 'Analytics Reports';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return AnalyticsReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnalyticsReportsTable::configure($table);
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
            'index' => ListAnalyticsReports::route('/'),
            'create' => CreateAnalyticsReport::route('/create'),
            'view' => ViewAnalyticsReport::route('/{record}'),
            'edit' => EditAnalyticsReport::route('/{record}/edit'),
        ];
    }
}
