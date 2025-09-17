<?php

namespace App\Filament\Resources\WebsiteAnalyses;

use App\Enums\NavigationGroups;
use App\Filament\Resources\WebsiteAnalyses\Pages\CreateWebsiteAnalysis;
use App\Filament\Resources\WebsiteAnalyses\Pages\EditWebsiteAnalysis;
use App\Filament\Resources\WebsiteAnalyses\Pages\ListWebsiteAnalyses;
use App\Filament\Resources\WebsiteAnalyses\RelationManagers\SectionsRelationManager;
use App\Filament\Resources\WebsiteAnalyses\Schemas\WebsiteAnalysisForm;
use App\Filament\Resources\WebsiteAnalyses\Tables\WebsiteAnalysesTable;
use App\Models\Project;
use App\Models\WebsiteAnalysis;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WebsiteAnalysisResource extends Resource
{
    protected static ?string $model = WebsiteAnalysis::class;

    //  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Weboldal elemzések';

    protected static ?string $tenantOwnershipRelationshipName = 'project';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroups::SeoManagement;

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        $builder = parent::getEloquentQuery();
        $tenant = Filament::getTenant();

        if ($tenant instanceof Project) {
            $builder->where('project_id', $tenant->id);
        }

        return $builder;
    }

    public static function form(Schema $schema): Schema
    {
        return WebsiteAnalysisForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebsiteAnalysesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebsiteAnalyses::route('/'),
            'create' => CreateWebsiteAnalysis::route('/create'),
            'edit' => EditWebsiteAnalysis::route('/{record}/edit'),
        ];
    }
}
