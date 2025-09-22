<?php

namespace App\Filament\Resources\SeoAnalyses;

use App\Enums\NavigationGroups;
use App\Filament\Resources\SeoAnalyses\Pages\CreateSeoAnalysis;
use App\Filament\Resources\SeoAnalyses\Pages\EditSeoAnalysis;
use App\Filament\Resources\SeoAnalyses\Pages\ListSeoAnalyses;
use App\Filament\Resources\SeoAnalyses\Schemas\SeoAnalysisForm;
use App\Filament\Resources\SeoAnalyses\Tables\SeoAnalysesTable;
use App\Models\Project;
use App\Models\SeoAnalysis;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SeoAnalysisResource extends Resource
{
    protected static ?string $model = SeoAnalysis::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroups::SeoManagement;

    protected static ?string $modelLabel = 'SEO Elemzés';

    protected static ?string $pluralModelLabel = 'SEO Elemzések';

    protected static ?int $navigationSort = 1;

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
        return SeoAnalysisForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeoAnalysesTable::configure($table);
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
            'index' => ListSeoAnalyses::route('/'),
            'create' => CreateSeoAnalysis::route('/create'),
            'edit' => EditSeoAnalysis::route('/{record}/edit'),
        ];
    }
}
