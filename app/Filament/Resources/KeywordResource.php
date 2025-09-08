<?php

namespace App\Filament\Resources;

use App\Enums\NavigationGroups;
use App\Filament\Resources\KeywordResource\RelationManagers\RankingsRelationManager;
use App\Filament\Resources\Keywords\Pages\CreateKeyword;
use App\Filament\Resources\Keywords\Pages\EditKeyword;
use App\Filament\Resources\Keywords\Pages\ListKeywords;
use App\Filament\Resources\Keywords\Schemas\KeywordForm;
use App\Filament\Resources\Keywords\Tables\KeywordsTable;
use App\Models\Keyword;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class KeywordResource extends Resource
{
    protected static ?string $model = Keyword::class;

    /*     protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass; */

    protected static string|UnitEnum|null $navigationGroup = NavigationGroups::SeoManagement;

    protected static ?int $navigationSort = 2;

    protected static ?string $tenantOwnershipRelationshipName = 'project';

    public static function getEloquentQuery(): Builder
    {
        $builder = parent::getEloquentQuery();
        $tenant = Filament::getTenant();

        if ($tenant) {
            $builder->where('project_id', $tenant->id);
        }

        return $builder;
    }

    public static function form(Schema $schema): Schema
    {
        return KeywordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KeywordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RankingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKeywords::route('/'),
            'create' => CreateKeyword::route('/create'),
            'edit' => EditKeyword::route('/{record}/edit'),
        ];
    }
}
