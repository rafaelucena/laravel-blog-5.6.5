<?php

namespace App\Listeners;

use App\Events\Event;
use App\Http\Models\Subscriber;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailSubscribedListener
{
    public $subscriber;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * Handle the event.
     *
     * @param  Event  $event
     * @return void
     */
    public function handle(Event $event)
    {
        //
    }
}
