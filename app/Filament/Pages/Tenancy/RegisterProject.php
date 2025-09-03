<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Project;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterProject extends RegisterTenant
{
    public function __construct(private readonly \Illuminate\Contracts\Auth\Guard $guard) {}

    public static function getLabel(): string
    {
        return 'Create New Project';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Project Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Main Website SEO'),

                TextInput::make('url')
                    ->label('Website URL')
                    ->url()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('https://example.com'),

                Textarea::make('description')
                    ->label('Description')
                    ->maxLength(500)
                    ->placeholder('Brief description of this SEO project'),
            ]);
    }

    protected function handleRegistration(array $data): Project
    {
        $project = \App\Models\Project::query()->create($data);

        // Attach the current user to the project
        $project->users()->attach($this->guard->user());

        return $project;
    }
}
