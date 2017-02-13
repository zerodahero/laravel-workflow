<?php

namespace Brexis\LaravelWorkflow\Events;

use Symfony\Component\Workflow\Event\GuardEvent;

class Guard
{
    /**
     * @var GuardEvent
     */
    public $event;

    public function __construct(GuardEvent $event)
    {
        $this->event = $event;
    }
}
