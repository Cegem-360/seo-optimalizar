<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project Details')
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter project name'),

                        TextInput::make('url')
                            ->required()
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://example.com'),

                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Optional project description'),
                    ])
                    ->columns(2),
            ]);
    }
}
