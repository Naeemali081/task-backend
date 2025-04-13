<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class UserTagged extends Notification
{
    use Queueable;

    protected $mentioningUser;
    protected $comment;
    protected $task;

    /**
     * Create a new notification instance.
     */
    public function __construct($mentioningUser, $comment, $task)
    {
        $this->mentioningUser = $mentioningUser;
        $this->comment = $comment;
        $this->task = $task;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
      $this->task->load('task_list.folder.phase');

      $project_id = $this->task->project_id;
      $task_list_id = $this->task->list_id;
      $task_id = $this->task->id;
      $folder_id = $this->task->task_list->folder_id ?? null;
      $phase_id = $this->task->task_list->folder->phase_id ?? null;
        return [
            'type' => 'user-tagged',
            'message' => $this->mentioningUser->first_name . " " . $this->mentioningUser->last_name. " mentioned you in a comment.",
            'comment' => $this->comment,
            'task' => $this->task,
            'project_id' => $project_id,
            'phase_id' => $phase_id,
            'folder_id' => $folder_id,
            'task_list_id' => $task_list_id,
            'task_id' => $task_id,
            'comment_id' => $this->comment->id,
            'url' => url('task/'.$this->comment->task_id .'/comments'),
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
      $frontendUrl = config('app.frontend_url');
        return (new MailMessage)
                    ->line($this->mentioningUser->first_name . $this->mentioningUser->last_name . ' mentioned you in a comment.')
                    ->line('Comment: ' . $this->comment['message'])
                    ->action('View Comment', url($frontendUrl))
                    ->line('Thank you for using our application!');                    
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
