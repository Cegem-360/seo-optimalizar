<?php

namespace App\Filament\Pages;

use App\Models\NotificationPreference;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

class ManageNotificationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected string $view = 'filament.pages.manage-notification-settings';


    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public NotificationPreference $preference;

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        $user = auth()->user();

        if (! $tenant || ! $user) {
            return;
        }

        $this->preference = NotificationPreference::firstOrCreate(
            [
                'user_id' => $user->id,
                'project_id' => $tenant->id,
            ],
            [
                'email_ranking_changes' => true,
                'email_top3_achievements' => true,
                'email_first_page_entries' => true,
                'email_significant_drops' => true,
                'email_weekly_summary' => false,
                'app_ranking_changes' => true,
                'app_top3_achievements' => true,
                'app_first_page_entries' => true,
                'app_significant_drops' => true,
                'significant_change_threshold' => 5,
                'only_significant_changes' => false,
            ]
        );

        $this->form->fill($this->preference->toArray());
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Email Notifications')
                ->description('Configure which notifications you want to receive via email')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Toggle::make('email_ranking_changes')
                                ->label('Ranking Changes')
                                ->helperText('Get notified when rankings change significantly'),

                            Toggle::make('email_top3_achievements')
                                ->label('Top 3 Achievements')
                                ->helperText('Celebrate when keywords reach top 3 positions'),

                            Toggle::make('email_first_page_entries')
                                ->label('First Page Entries')
                                ->helperText('Know when keywords enter the first page'),

                            Toggle::make('email_significant_drops')
                                ->label('Significant Drops')
                                ->helperText('Alert when rankings drop significantly'),

                            Toggle::make('email_weekly_summary')
                                ->label('Weekly Summary')
                                ->helperText('Receive a weekly SEO performance summary')
                                ->columnSpan(2),
                        ]),
                ]),

            Section::make('In-App Notifications')
                ->description('Configure which notifications appear in the application')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Toggle::make('app_ranking_changes')
                                ->label('Ranking Changes'),

                            Toggle::make('app_top3_achievements')
                                ->label('Top 3 Achievements'),

                            Toggle::make('app_first_page_entries')
                                ->label('First Page Entries'),

                            Toggle::make('app_significant_drops')
                                ->label('Significant Drops'),
                        ]),
                ]),

            Section::make('Notification Thresholds')
                ->description('Fine-tune when notifications should be triggered')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('significant_change_threshold')
                                ->label('Significant Change Threshold')
                                ->helperText('Number of positions to consider as significant')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(20)
                                ->default(5),

                            Toggle::make('only_significant_changes')
                                ->label('Only Significant Changes')
                                ->helperText('Only notify for changes above the threshold'),
                        ]),
                ]),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('save'),

            Action::make('testNotification')
                ->label('Send Test Notification')
                ->color('gray')
                ->action('sendTestNotification'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->preference->update($data);

        Notification::make()
            ->title('Settings saved')
            ->body('Your notification preferences have been updated.')
            ->success()
            ->send();
    }

    public function sendTestNotification(): void
    {
        $user = auth()->user();

        // Create a test ranking change notification
        $testRanking = new \stdClass();
        $testRanking->position = 3;
        $testRanking->previous_position = 8;
        $testRanking->keyword = (object) [
            'keyword' => 'test keyword',
            'project' => Filament::getTenant(),
        ];

        try {
            $channels = [];

            if ($this->preference->email_top3_achievements) {
                $channels[] = 'mail';
            }

            if ($this->preference->app_top3_achievements) {
                $channels[] = 'database';
            }

            if (empty($channels)) {
                Notification::make()
                    ->title('No channels enabled')
                    ->body('Please enable at least one notification channel for Top 3 Achievements to send a test.')
                    ->warning()
                    ->send();

                return;
            }

            $user->notify(new \App\Notifications\RankingChangeNotification(
                $testRanking,
                'top3',
                app('url'),
                $channels
            ));

            Notification::make()
                ->title('Test notification sent')
                ->body('A test notification has been sent to your configured channels.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to send test')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
