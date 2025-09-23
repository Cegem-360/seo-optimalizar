<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RankingChangeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $ranking,
        public string $changeType,
        private readonly string $baseUrl,
        private readonly array $channels = ['mail', 'database'],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $keyword = $this->ranking->keyword;
        $project = $keyword->project;
        $change = $this->calculateChange();

        $subject = match ($this->changeType) {
            'top3' => '🎉 Top 3 Achievement: ' . $keyword->keyword,
            'first_page' => '🚀 First Page Ranking: ' . $keyword->keyword,
            'dropped_out' => '⚠️ Ranking Drop Alert: ' . $keyword->keyword,
            'significant_improvement' => '📈 Significant Improvement: ' . $keyword->keyword,
            'significant_decline' => '📉 Significant Decline: ' . $keyword->keyword,
            default => 'Ranking Update: ' . $keyword->keyword,
        };

        $mailMessage = (new MailMessage())
            ->subject($subject)
            ->greeting(sprintf('Hello %s!', $notifiable->name))
            ->line($this->getChangeDescription())
            ->line('**Project:** ' . $project->name)
            ->line('**Keyword:** ' . $keyword->keyword)
            ->line('**Current Position:** ' . $this->ranking->position)
            ->line('**Previous Position:** ' . ($this->ranking->previous_position ?? 'New'))
            ->line('**Change:** ' . $change)
            ->action('View Details', $this->baseUrl . sprintf('/admin/%s/rankings', $project->id));

        if ($this->ranking->url) {
            $mailMessage->line('**Ranking URL:** ' . $this->ranking->url);
        }

        return $mailMessage->line('Keep up the great SEO work!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ranking_id' => $this->ranking->id ?? null,
            'keyword' => $this->ranking->keyword->keyword,
            'project' => $this->ranking->keyword->project->name,
            'change_type' => $this->changeType,
            'current_position' => $this->ranking->position,
            'previous_position' => $this->ranking->previous_position,
            'change' => $this->calculateChange(),
        ];
    }

    private function calculateChange(): string
    {
        if (! $this->ranking->previous_position) {
            return 'NEW';
        }

        $change = $this->ranking->previous_position - $this->ranking->position;
        if ($change > 0) {
            return sprintf('+%s positions', $change);
        }

        if ($change < 0) {
            return $change . ' positions';
        }

        return 'No change';
    }

    private function getChangeDescription(): string
    {
        return match ($this->changeType) {
            'top3' => 'Congratulations! Your keyword has reached the top 3 positions on Google!',
            'first_page' => 'Great news! Your keyword has reached the first page of Google search results.',
            'dropped_out' => 'Your keyword has dropped out of the first page. It might need some attention.',
            'significant_improvement' => 'Your keyword has shown significant improvement in rankings!',
            'significant_decline' => 'Your keyword has experienced a significant decline in rankings.',
            default => 'Your keyword ranking has been updated.',
        };
    }
}
