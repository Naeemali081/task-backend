<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Notifications\BroadcastProjectMessage;

class SendBroadcastEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notifiable;
    protected $notification;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\User $notifiable
     * @param \App\Notifications\BroadcastProjectMessage $notification
     */
    public function __construct(User $notifiable, BroadcastProjectMessage $notification)
    {
        $this->notifiable = $notifiable;
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->notifiable->notify($this->notification);
        } catch (\Exception $e) {
            \Log::error("Failed to send notification to {$this->notifiable->email}: {$e->getMessage()}");
        }
    }
}
