<?php

namespace App\Filament\Resources\CompetitorAnalyses;

use App\Filament\Resources\CompetitorAnalyses\Pages\CreateCompetitorAnalysis;
use App\Filament\Resources\CompetitorAnalyses\Pages\EditCompetitorAnalysis;
use App\Filament\Resources\CompetitorAnalyses\Pages\ListCompetitorAnalyses;
use App\Filament\Resources\CompetitorAnalyses\Schemas\CompetitorAnalysisForm;
use App\Filament\Resources\CompetitorAnalyses\Tables\CompetitorAnalysesTable;
use App\Models\CompetitorAnalysis;
use App\Models\Project;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CompetitorAnalysisResource extends Resource
{
    protected static ?string $model = CompetitorAnalysis::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'SEO Analytics';

    protected static ?string $modelLabel = 'Versenytárs elemzés';

    protected static ?string $pluralModelLabel = 'Versenytárs elemzések';

    protected static ?int $navigationSort = 3;

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
        return CompetitorAnalysisForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompetitorAnalysesTable::configure($table);
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
            'index' => ListCompetitorAnalyses::route('/'),
            'create' => CreateCompetitorAnalysis::route('/create'),
            'edit' => EditCompetitorAnalysis::route('/{record}/edit'),
        ];
    }
}
