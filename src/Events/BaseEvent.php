<?php

namespace ZeroDaHero\LaravelWorkflow\Events;

use Workflow;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent as SymfonyGuardEvent;

/**
 * @method \Symfony\Component\Workflow\Marking getMarking()
 * @method object getSubject()
 * @method \Symfony\Component\Workflow\Transition getTransition()
 * @method \Symfony\Component\Workflow\WorkflowInterface getWorkflow()
 * @method string getWorkflowName()
 * @method mixed getMetadata(string $key, $subject)
 */
abstract class BaseEvent extends Event
{
    public function __serialize(): array
    {
        $result = [
            'base_event_class' => get_class($this),
            'subject' => $this->getSubject(),
            'marking' => $this->getMarking(),
            'transition' => $this->getTransition(),
            'workflow' => [
                'name' => $this->getWorkflowName(),
            ],
        ];
        if (!$this instanceof GuardEvent) {
            $result['context'] = $this->getContext();
        }

        return $result;
    }

    public function __unserialize(array $data): void
    {
        $workflowName = $data['workflow']['name'] ?? null;

        $arguments = [
            ...$data,
            'workflow' => Workflow::get($data['subject'], $workflowName),
        ];
        if (!$this instanceof GuardEvent) {
            $arguments['context'] = $data['context'];
        }

        parent::__construct(...$arguments);
    }

    /**
     * Creates a new instance from the base Symfony event
     */
    public static function newFromBase(Event $symfonyEvent)
    {
        $arguments = [
            'subject' => $symfonyEvent->getSubject(),
            'marking' => $symfonyEvent->getMarking(),
            'transition' => $symfonyEvent->getTransition(),
            'workflow' => $symfonyEvent->getWorkflow(),
        ];
        if (!$symfonyEvent instanceof SymfonyGuardEvent) {
            $arguments['context'] = $symfonyEvent->getContext();
        }

        return new static(...$arguments);
    }
}
