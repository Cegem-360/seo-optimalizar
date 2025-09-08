<?php

namespace App\Filament\Resources\PageSpeedResults\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageSpeedResultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Run PageSpeed Analysis')
                    ->description('Click "Create" to start the PageSpeed analysis for the current project. The results will be saved automatically.')
                    ->components([
                        Select::make('strategy')
                            ->label('Analysis Strategy')
                            ->options([
                                'mobile' => 'Mobile',
                                'desktop' => 'Desktop',
                            ])
                            ->default('mobile')
                            ->required()
                            ->helperText('Choose whether to analyze the mobile or desktop version of your site'),
                    ]),
            ]);
    }
}
