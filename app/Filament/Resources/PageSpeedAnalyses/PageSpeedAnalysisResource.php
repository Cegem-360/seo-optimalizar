<?php

namespace App\Filament\Resources\PageSpeedAnalyses;

use App\Filament\Resources\PageSpeedAnalyses\Pages\CreatePageSpeedAnalysis;
use App\Filament\Resources\PageSpeedAnalyses\Pages\EditPageSpeedAnalysis;
use App\Filament\Resources\PageSpeedAnalyses\Pages\ListPageSpeedAnalyses;
use App\Filament\Resources\PageSpeedAnalyses\Schemas\PageSpeedAnalysisForm;
use App\Filament\Resources\PageSpeedAnalyses\Tables\PageSpeedAnalysesTable;
use App\Models\PageSpeedAnalysis;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PageSpeedAnalysisResource extends Resource
{
    protected static ?string $model = PageSpeedAnalysis::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'SEO Analytics';

    protected static ?string $modelLabel = 'Sebesség elemzés';

    protected static ?string $pluralModelLabel = 'Sebesség elemzések';

    protected static ?int $navigationSort = 2;

    protected static ?string $tenantOwnershipRelationshipName = 'project';

    public static function getEloquentQuery(): Builder
    {
        $builder = parent::getEloquentQuery();
        $tenant = Filament::getTenant();

        if ($tenant instanceof \App\Models\Project) {
            $builder->where('project_id', $tenant->id);
        }

        return $builder;
    }

    public static function form(Schema $schema): Schema
    {
        return PageSpeedAnalysisForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PageSpeedAnalysesTable::configure($table);
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
            'index' => ListPageSpeedAnalyses::route('/'),
            'create' => CreatePageSpeedAnalysis::route('/create'),
            'edit' => EditPageSpeedAnalysis::route('/{record}/edit'),
        ];
    }
}
