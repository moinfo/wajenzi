<?php

namespace App\Notifications;

use App\Models\ProjectScheduleActivity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivityReassignedNotification extends Notification
{
    use Queueable;

    protected $activity;
    protected $assignedBy;
    protected $channels = ['mail', 'database'];

    public function __construct(ProjectScheduleActivity $activity, User $assignedBy)
    {
        $this->activity = $activity;
        $this->assignedBy = $assignedBy;
    }

    /**
     * Restrict to database channel only
     */
    public function onlyDatabase(): self
    {
        $this->channels = ['database'];
        return $this;
    }

    /**
     * Restrict to mail channel only
     */
    public function onlyMail(): self
    {
        $this->channels = ['mail'];
        return $this;
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $schedule = $this->activity->schedule;

        return (new MailMessage)
            ->subject("Activity Assigned: {$this->activity->activity_code} - {$this->activity->name}")
            ->view('emails.activity-reassigned', [
                'activity' => $this->activity,
                'schedule' => $schedule,
                'assignedBy' => $this->assignedBy,
                'userName' => $notifiable->name,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $schedule = $this->activity->schedule;

        return [
            'title' => 'Activity Assigned to You',
            'body' => "Activity {$this->activity->activity_code}: {$this->activity->name} has been assigned to you by {$this->assignedBy->name}.",
            'link' => '/project-schedules/' . $schedule->id,
            'document_id' => $this->activity->id,
            'type' => 'activity_reassigned',
            'activity_code' => $this->activity->activity_code,
            'schedule_id' => $schedule->id,
        ];
    }
}
