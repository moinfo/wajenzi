<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Approved implements ShouldBroadcast{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $details;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $staff_id = $this->details['staff_id'];
        return new PrivateChannel('details'.$staff_id);
    }
}
