<?php

namespace ZeroDaHero\LaravelWorkflow\Events;

use Symfony\Component\Workflow\Event\GuardEvent as SymfonyGuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;
use Symfony\Component\Workflow\TransitionBlockerList;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class GuardEvent extends BaseEvent
{
    public function __construct(SymfonyGuardEvent $event)
    {
        $this->originalEvent = $event;
    }

    public function isBlocked(): bool
    {
        return $this->originalEvent->isBlocked();
    }

    public function setBlocked(bool $blocked, string $message = null): void
    {
        $this->originalEvent->setBlocked($blocked, $message);
    }

    public function getTransitionBlockerList(): TransitionBlockerList
    {
        return $this->originalEvent->getTransitionBlockerList();
    }

    public function addTransitionBlocker(TransitionBlocker $transitionBlocker): void
    {
        $this->originalEvent->addTransitionBlocker($transitionBlocker);
    }
}
