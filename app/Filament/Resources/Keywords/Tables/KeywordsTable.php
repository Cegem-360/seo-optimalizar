<?php

declare(strict_types=1);

namespace App\Filament\Resources\Keywords\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class KeywordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('keyword')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('geo_target')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('priority')
                    ->options([
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),

                SelectFilter::make('intent_type')
                    ->options([
                        'informational' => 'Informational',
                        'navigational' => 'Navigational',
                        'commercial' => 'Commercial',
                        'transactional' => 'Transactional',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('set_high_priority')
                    ->label('Kiemelt')
                    ->icon('heroicon-o-star')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Kiemelt prioritás beállítása')
                    ->modalDescription('Biztosan kiemelt prioritásúvá szeretné tenni ezt a kulcsszót?')
                    ->modalSubmitActionLabel('Igen, kiemel')
                    ->action(function ($record): void {
                        $record->update(['priority' => 'high']);

                        Notification::make()
                            ->title('Sikeres módosítás')
                            ->body('A kulcsszó kiemelt prioritású lett.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => $record->priority !== 'high'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('set_high_priority_bulk')
                        ->label('Kiemelt prioritás')
                        ->icon('heroicon-o-star')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Kiemelt prioritás beállítása')
                        ->modalDescription('Biztosan kiemelt prioritásúvá szeretné tenni a kiválasztott kulcsszavakat?')
                        ->modalSubmitActionLabel('Igen, mind kiemel')
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->priority !== 'high') {
                                    $record->update(['priority' => 'high']);
                                    $updated++;
                                }
                            }

                            Notification::make()
                                ->title('Sikeres módosítás')
                                ->body("{$updated} kulcsszó kiemelt prioritású lett.")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('set_medium_priority_bulk')
                        ->label('Közepes prioritás')
                        ->icon('heroicon-o-minus-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Közepes prioritás beállítása')
                        ->modalDescription('Biztosan közepes prioritásúvá szeretné tenni a kiválasztott kulcsszavakat?')
                        ->modalSubmitActionLabel('Igen, közepes')
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->priority !== 'medium') {
                                    $record->update(['priority' => 'medium']);
                                    $updated++;
                                }
                            }

                            Notification::make()
                                ->title('Sikeres módosítás')
                                ->body("{$updated} kulcsszó közepes prioritású lett.")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('set_low_priority_bulk')
                        ->label('Alacsony prioritás')
                        ->icon('heroicon-o-arrow-down-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Alacsony prioritás beállítása')
                        ->modalDescription('Biztosan alacsony prioritásúvá szeretné tenni a kiválasztott kulcsszavakat?')
                        ->modalSubmitActionLabel('Igen, alacsony')
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->priority !== 'low') {
                                    $record->update(['priority' => 'low']);
                                    $updated++;
                                }
                            }

                            Notification::make()
                                ->title('Sikeres módosítás')
                                ->body("{$updated} kulcsszó alacsony prioritású lett.")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
