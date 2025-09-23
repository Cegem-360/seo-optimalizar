<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApiCredentials;

use App\Enums\NavigationGroups;
use App\Filament\Resources\ApiCredentials\Pages\CreateApiCredential;
use App\Filament\Resources\ApiCredentials\Pages\EditApiCredential;
use App\Filament\Resources\ApiCredentials\Pages\ListApiCredentials;
use App\Filament\Resources\ApiCredentials\Schemas\ApiCredentialForm;
use App\Filament\Resources\ApiCredentials\Tables\ApiCredentialsTable;
use App\Models\ApiCredential;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ApiCredentialResource extends Resource
{
    protected static ?string $model = ApiCredential::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroups::Settings;

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
}
