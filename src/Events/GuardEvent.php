<?php

namespace ZeroDaHero\LaravelWorkflow\Events;

use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Workflow\Event\GuardEvent as SymfonyGuardEvent;

/**
 * @method bool isBlocked()
 * @method void setBlocked(bool $blocked, string $message = null)
 * @method Transition getTransition()
 * @method TransitionBlockerList getTransitionBlockerList()
 * @method void addTransitionBlocker(TransitionBlocker $transitionBlocker)
 */
class GuardEvent extends BaseEvent
{
    private SymfonyGuardEvent $symfonyProxyEvent;

    public function __construct(object $subject, Marking $marking, Transition $transition, WorkflowInterface $workflow = null)
    {
        parent::__construct($subject, $marking, $transition, $workflow);

        $this->symfonyProxyEvent = new SymfonyGuardEvent($subject, $marking, $transition, $workflow);
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->symfonyProxyEvent, $name)) {
            return call_user_func_array([$this->symfonyProxyEvent,$name], $arguments);
        }
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        $this->symfonyProxyEvent = new SymfonyGuardEvent(
            $this->getSubject(),
            $this->getMarking(),
            $this->getTransition(),
            $this->getWorkflow(),
            $this->getContext()
        );
    }

    /**
     * Creates a new instance from the base Symfony event
     */
    public static function newFromBase(Event $symfonyEvent)
    {
        $instance = new static(
            $symfonyEvent->getSubject(),
            $symfonyEvent->getMarking(),
            $symfonyEvent->getTransition(),
            $symfonyEvent->getWorkflow(),
            $symfonyEvent->getContext()
        );

        $instance->symfonyProxyEvent = $symfonyEvent;

        return $instance;
    }
}
