<?php

namespace App\Filament\Resources\GoogleAdsReports;

use App\Filament\Resources\GoogleAdsReports\Pages\CreateGoogleAdsReport;
use App\Filament\Resources\GoogleAdsReports\Pages\EditGoogleAdsReport;
use App\Filament\Resources\GoogleAdsReports\Pages\ListGoogleAdsReports;
use App\Filament\Resources\GoogleAdsReports\Pages\ViewGoogleAdsReport;
use App\Filament\Resources\GoogleAdsReports\Schemas\GoogleAdsReportForm;
use App\Filament\Resources\GoogleAdsReports\Tables\GoogleAdsReportsTable;
use App\Models\GoogleAdsReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GoogleAdsReportResource extends Resource
{
    protected static ?string $model = GoogleAdsReport::class;

    protected static ?string $navigationLabel = 'Google Ads Reports';

    protected static ?string $modelLabel = 'Google Ads Report';

    protected static ?string $pluralModelLabel = 'Google Ads Reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return GoogleAdsReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GoogleAdsReportsTable::configure($table);
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
            'index' => ListGoogleAdsReports::route('/'),
            'create' => CreateGoogleAdsReport::route('/create'),
            'view' => ViewGoogleAdsReport::route('/{record}'),
            'edit' => EditGoogleAdsReport::route('/{record}/edit'),
        ];
    }
}
