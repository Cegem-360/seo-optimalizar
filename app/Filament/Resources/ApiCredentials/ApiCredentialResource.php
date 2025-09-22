<?php

namespace App\Filament\Resources\ApiCredentials;

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

class ApiCredentialResource extends Resource
{
    protected static ?string $model = ApiCredential::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
