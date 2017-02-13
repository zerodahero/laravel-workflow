<?php

namespace Brexis\LaravelWorkflow\Events;

use Symfony\Component\Workflow\Event\Event;

class Leave
{
    /**
     * @var Event
     */
    public $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }
}
