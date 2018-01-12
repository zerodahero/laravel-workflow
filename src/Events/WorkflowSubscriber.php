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
    public function guardEvent(SymfonyGuardEvent $event)
    {
        event(new GuardEvent($event));
        event('workflow.guard', $event);
        event('workflow.' . $event->getWorkflowName() . '.guard', $event);
        event('workflow.' . $event->getWorkflowName() . '.guard.' . $event->getTransition()->getName(), $event);
    }

    public function leaveEvent(Event $event)
    {
        event(new LeaveEvent($event));
        event('workflow.leave', $event);
        event('workflow.' . $event->getWorkflowName() . '.leave', $event);
        foreach ($event->getTransition()->getFroms() as $marking) {
            event('workflow.' . $event->getWorkflowName() . '.leave.' . $marking, $event);
        }
    }

    public function transitionEvent(Event $event)
    {
        event(new TransitionEvent($event));
        event('workflow.transition', $event);
        event('workflow.' . $event->getWorkflowName() . '.transition', $event);
        event('workflow.' . $event->getWorkflowName() . '.transition.' . $event->getTransition()->getName(), $event);
    }

    public function enterEvent(Event $event)
    {
        event(new EnterEvent($event));
        event('workflow.enter', $event);
        event('workflow.' . $event->getWorkflowName() . '.enter', $event);
        foreach ($event->getTransition()->getTos() as $marking) {
            event('workflow.' . $event->getWorkflowName() . '.enter.' . $marking, $event);
        }
    }

    public function enteredEvent(Event $event)
    {
        event(new EnteredEvent($event));
        event('workflow.entered', $event);
        event('workflow.' . $event->getWorkflowName() . '.entered', $event);
        foreach ($event->getTransition()->getTos() as $marking) {
            event('workflow.' . $event->getWorkflowName() . '.entered.' . $marking, $event);
        }
    }

    public function completedEvent(Event $event)
    {
        event(new CompletedEvent($event));
        event('workflow.completed', $event);
        event('workflow.' . $event->getWorkflowName() . '.completed', $event);
        event('workflow.' . $event->getWorkflowName() . '.completed.' . $event->getTransition()->getName(), $event);
    }


    public static function getSubscribedEvents()
    {
        return [
            'workflow.guard'      => ['guardEvent'],
            'workflow.leave'      => ['leaveEvent'],
            'workflow.transition' => ['transitionEvent'],
            'workflow.enter'      => ['enterEvent'],
            'workflow.entered'    => ['enteredEvent'],
            'workflow.completed'  => ['completedEvent'],
        ];
    }
}
