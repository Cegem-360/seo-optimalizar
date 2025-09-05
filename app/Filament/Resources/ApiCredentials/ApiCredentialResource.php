<?php

namespace App\Filament\Resources\ApiCredentials;

use App\Enums\NavigationGroups;
use App\Filament\Resources\ApiCredentials\Pages\CreateApiCredential;
use App\Filament\Resources\ApiCredentials\Pages\EditApiCredential;
use App\Filament\Resources\ApiCredentials\Pages\ListApiCredentials;
use App\Filament\Resources\ApiCredentials\Schemas\ApiCredentialForm;
use App\Filament\Resources\ApiCredentials\Tables\ApiCredentialsTable;
use App\Models\ApiCredential;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ApiCredentialResource extends Resource
{
    protected static ?string $model = ApiCredential::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroups::Settings;

    protected static ?string $navigationLabel = 'API Credentials';

    protected static ?string $modelLabel = 'API Credential';

    protected static ?string $pluralModelLabel = 'API Credentials';

    protected static ?int $navigationSort = 90;

    protected static ?string $tenantOwnershipRelationshipName = 'project';

    public static function form(Schema $schema): Schema
    {
        return ApiCredentialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiCredentialsTable::configure($table);
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
            'index' => ListApiCredentials::route('/'),
            'create' => CreateApiCredential::route('/create'),
            'edit' => EditApiCredential::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getNavigationBadge();
        return $count > 0 ? 'success' : 'warning';
    }
}
