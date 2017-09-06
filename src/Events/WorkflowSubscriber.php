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
        event('workflow.'.$event->getWorkflowName().'.guard', $event);
    }

    public function leaveEvent(Event $event) {
        event('workflow.'.$event->getWorkflowName().'.leave', $event);
    }

    public function transitionEvent(Event $event) {
        event('workflow.'.$event->getWorkflowName().'.transition', $event);
    }

    public function enterEvent(Event $event) {
        event('workflow.'.$event->getWorkflowName().'.enter', $event);
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
