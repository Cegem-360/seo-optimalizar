<?php

namespace App\Filament\Resources\SerpAnalysisResults;

use App\Enums\NavigationGroups;
use App\Filament\Resources\SerpAnalysisResults\Pages\EditSerpAnalysisResult;
use App\Filament\Resources\SerpAnalysisResults\Pages\ListSerpAnalysisResults;
use App\Filament\Resources\SerpAnalysisResults\Pages\ViewSerpAnalysisResult;
use App\Filament\Resources\SerpAnalysisResults\Schemas\SerpAnalysisResultForm;
use App\Filament\Resources\SerpAnalysisResults\Schemas\SerpAnalysisResultInfolist;
use App\Filament\Resources\SerpAnalysisResults\Tables\SerpAnalysisResultsTable;
use App\Models\SerpAnalysisResult;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class SerpAnalysisResultResource extends Resource
{
    protected static ?string $model = SerpAnalysisResult::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroups::SeoTools;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'SERP Elemzések';

    public static function getNavigationLabel(): string
    {
        return 'SERP Elemzések';
    }

    public static function getModelLabel(): string
    {
        return 'SERP Elemzés';
    }

    public static function getPluralModelLabel(): string
    {
        return 'SERP Elemzések';
    }

    public static function form(Schema $schema): Schema
    {
        return SerpAnalysisResultForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SerpAnalysisResultInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SerpAnalysisResultsTable::configure($table);
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
            'index' => ListSerpAnalysisResults::route('/'),
            'view' => ViewSerpAnalysisResult::route('/{record}'),
            'edit' => EditSerpAnalysisResult::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
