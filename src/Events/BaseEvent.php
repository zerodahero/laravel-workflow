<?php

namespace ZeroDaHero\LaravelWorkflow\Events;

use Serializable;
use Symfony\Component\Workflow\Event\Event;
use Workflow;

/**
 * @method Marking getMarking()
 * @method object getSubject()
 * @method Transition getTransition()
 * @method WorkflowInterface getWorkflow()
 * @method string getWorkflowName()
 * @method mixed getMetadata(string $key, $subject)
 */
abstract class BaseEvent implements Serializable
{
    /**
     * @var Event
     */
    protected $originalEvent;

    public function __construct(Event $event)
    {
        $this->originalEvent = $event;
    }

    /**
     * Return the original event
     *
     * @return Event
     */
    public function getOriginalEvent()
    {
        return $this->originalEvent;
    }

    public function serialize()
    {
        return serialize([
            'base_event_class' => get_class($this->originalEvent),
            'subject' => serialize($this->originalEvent->getSubject()),
            'marking' => serialize($this->originalEvent->getMarking()),
            'transition' => serialize($this->originalEvent->getTransition()),
            'workflow' => [
                'name' => $this->originalEvent->getWorkflowName()
            ],
        ]);
    }

    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $subject = unserialize($unserialized['subject']);
        $marking = unserialize($unserialized['marking']);
        $transition = unserialize($unserialized['transition'] ?? null);
        $workflowName = $unserialized['workflow']['name'] ?? null;
        $workflow = Workflow::get($subject, $workflowName);

        $eventClass = $unserialized['base_event_class'] ?? Event::class;
        $event = new $eventClass($subject, $marking, $transition, $workflow);

        $this->originalEvent = $event;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->originalEvent, $name)) {
            return call_user_func_array([$this->originalEvent, $name], $arguments);
        }
    }
}
