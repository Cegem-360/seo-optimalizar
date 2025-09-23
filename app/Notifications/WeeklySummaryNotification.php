<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklySummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public array $summaryData,
        private readonly UrlGenerator $urlGenerator
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $weekStart = Carbon::now()->startOfWeek()->format('M j');
        $weekEnd = Carbon::now()->endOfWeek()->format('M j, Y');

        $mailMessage = (new MailMessage())
            ->subject(sprintf('📊 Weekly SEO Summary: %s (%s - %s)', $this->project->name, $weekStart, $weekEnd))
            ->greeting(sprintf('Hello %s!', $notifiable->name))
            ->line(sprintf("Here's your weekly SEO summary for **%s**", $this->project->name))
            ->line(sprintf('**Week:** %s - %s', $weekStart, $weekEnd))
            ->line('---');

        // Overall Stats
        $mailMessage->line('## 📈 **Overall Performance**');
        $mailMessage->line('**Total Keywords:** ' . $this->summaryData['total_keywords']);
        $mailMessage->line('**Average Position:** ' . number_format($this->summaryData['avg_position'], 1));
        $mailMessage->line('**Keywords in Top 10:** ' . $this->summaryData['top_10_count']);
        $mailMessage->line('**Keywords in Top 3:** ' . $this->summaryData['top_3_count']);
        $mailMessage->line('');

        // Changes This Week
        if (! empty($this->summaryData['improvements']) || ! empty($this->summaryData['declines'])) {
            $mailMessage->line('## 🔄 **Changes This Week**');

            if (! empty($this->summaryData['improvements'])) {
                $mailMessage->line(sprintf('### ✅ **Improvements** (%s)', $this->summaryData['improvements_count']));
                foreach (array_slice($this->summaryData['improvements'], 0, 5) as $improvement) {
                    $change = $improvement['previous_position'] - $improvement['current_position'];
                    $mailMessage->line(sprintf('• **%s**: %s → %s (+%s)', $improvement['keyword'], $improvement['previous_position'], $improvement['current_position'], $change));
                }

                if (count($this->summaryData['improvements']) > 5) {
                    $remaining = count($this->summaryData['improvements']) - 5;
                    $mailMessage->line(sprintf('• ... and %d more improvements', $remaining));
                }

                $mailMessage->line('');
            }

            if (! empty($this->summaryData['declines'])) {
                $mailMessage->line(sprintf('### ⚠️ **Declines** (%s)', $this->summaryData['declines_count']));
                foreach (array_slice($this->summaryData['declines'], 0, 5) as $decline) {
                    $change = $decline['previous_position'] - $decline['current_position'];
                    $mailMessage->line(sprintf('• **%s**: %s → %s (%s)', $decline['keyword'], $decline['previous_position'], $decline['current_position'], $change));
                }

                if (count($this->summaryData['declines']) > 5) {
                    $remaining = count($this->summaryData['declines']) - 5;
                    $mailMessage->line(sprintf('• ... and %d more declines', $remaining));
                }

                $mailMessage->line('');
            }
        }

        // Opportunities
        if (! empty($this->summaryData['opportunities'])) {
            $mailMessage->line('## 🎯 **Optimization Opportunities**');
            $mailMessage->line('Keywords close to first page (positions 11-15):');
            foreach (array_slice($this->summaryData['opportunities'], 0, 5) as $opportunity) {
                $mailMessage->line(sprintf('• **%s** - Position %s', $opportunity['keyword'], $opportunity['position']));
            }

            if (count($this->summaryData['opportunities']) > 5) {
                $remaining = count($this->summaryData['opportunities']) - 5;
                $mailMessage->line(sprintf('• ... and %d more opportunities', $remaining));
            }
        }

        return $mailMessage
            ->action('View Full Report', $this->urlGenerator->to('/admin/' . $this->project->id))
            ->line('Keep optimizing for better results! 🚀');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'summary_data' => $this->summaryData,
            'week_start' => Carbon::now()->startOfWeek()->format('Y-m-d'),
            'week_end' => Carbon::now()->endOfWeek()->format('Y-m-d'),
        ];
    }
}
