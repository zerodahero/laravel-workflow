<?php

namespace Brexis\LaravelWorkflow\Events;

use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent as SymfonyGuardEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class WorkflowSubscriber implements EventSubscriberInterface
{
    public function guardEvent(SymfonyGuardEvent $event) {
        event(new GuardEvent($event));
    }

    public function leaveEvent(Event $event) {
        event(new LeaveEvent($event));
    }

    public function transitionEvent(Event $event) {
        event(new TransitionEvent($event));
    }

    public function enterEvent(Event $event) {
        event(new EnterEvent($event));
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
