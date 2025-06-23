<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class PostPublished implements ShouldBroadcast {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;

    public function __construct($post) {
        $this->post = $post;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new Channel('posts');
    }

    public function broadcastWith() {
        return [
            'title' => $this->post->title,
        ];
    }
}

