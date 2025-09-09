<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroups;
use App\Models\NotificationPreference;
use App\Notifications\RankingChangeNotification;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use stdClass;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageNotificationSettings extends Page implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /*     protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell; */

    protected static string|UnitEnum|null $navigationGroup = NavigationGroups::Settings;

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $title = 'Notification Settings';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    protected string $view = 'filament.pages.manage-notification-settings';

    public function __construct(private readonly AuthManager $authManager, private readonly Repository $repository) {}

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function saveAction(): Action
    {
        return Action::make('save')
            ->label('Save Settings')
            ->action('save');
    }

    public function testNotificationAction(): Action
    {
        return Action::make('testNotification')
            ->label('Send Test Notification')
            ->color('gray')
            ->action('sendTestNotification');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $record = $this->getRecord();

        if (! $record instanceof NotificationPreference) {
            $tenant = Filament::getTenant();
            $user = $this->authManager->user();

            $record = new NotificationPreference();
            $record->user_id = $user->id;
            $record->project_id = $tenant->id;
        }

        $record->fill($data);
        $record->save();

        if ($record->wasRecentlyCreated) {
            $this->form->record($record)->saveRelationships();
        }

        Notification::make()
            ->title('Settings saved')
            ->body('Your notification preferences have been updated.')
            ->success()
            ->send();
    }

    public function sendTestNotification(): void
    {
        $user = $this->authManager->user();
        $preference = $this->getRecord();

        // Create a test ranking change notification
        $testRanking = new stdClass();
        $testRanking->position = 3;
        $testRanking->previous_position = 8;
        $testRanking->keyword = (object) [
            'keyword' => 'test keyword',
            'project' => Filament::getTenant(),
        ];

        try {
            $channels = [];

            if ($preference?->email_top3_achievements) {
                $channels[] = 'mail';
            }

            if ($preference?->app_top3_achievements) {
                $channels[] = 'database';
            }

            if ($channels === []) {
                Notification::make()
                    ->title('No channels enabled')
                    ->body('Please enable at least one notification channel for Top 3 Achievements to send a test.')
                    ->warning()
                    ->send();

                return;
            }

            $user->notify(new RankingChangeNotification(
                $testRanking,
                'top3',
                $this->repository->get('app.url'),
                $channels
            ));

            Notification::make()
                ->title('Test notification sent')
                ->body('A test notification has been sent to your configured channels.')
                ->success()
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->title('Failed to send test')
                ->body('Error: ' . $exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getRecord(): ?NotificationPreference
    {
        $tenant = Filament::getTenant();
        $user = $this->authManager->user();

        if (! $tenant || ! $user) {
            return null;
        }

        return NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('project_id', $tenant->id)
            ->first();
    }
}
