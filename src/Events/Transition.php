<?php

namespace Brexis\LaravelWorkflow\Events;

use Symfony\Component\Workflow\Event\Event;

class Transition
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
