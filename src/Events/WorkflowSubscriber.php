<?php

namespace Brexis\LaravelWorkflow\Events;

use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WorkflowSubscriber implements EventSubscriberInterface
{
    public function guardEvent(GuardEvent $event) {
        event(new Guard($event));
    }

    public function leaveEvent(Event $event) {
        event(new Leave($event));
    }

    public function transitionEvent(Event $event) {
        event(new Transition($event));
    }

    public function enterEvent(Event $event) {
        event(new Enter($event));
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.guard'        => ['guardEvent'],
            'workflow.leave'        => ['leaveEvent'],
            'workflow.transition'   => ['transitionEvent'],
            'workflow.enter'        => ['enterEvent']
        ];
    }
}
