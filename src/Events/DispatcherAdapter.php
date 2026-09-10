<?php

namespace ZeroDaHero\LaravelWorkflow\Events;

use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DispatcherAdapter implements EventDispatcherInterface
{
    private const EVENT_MAP = [
        'guard' => GuardEvent::class,
        'leave' => LeaveEvent::class,
        'transition' => TransitionEvent::class,
        'enter' => EnterEvent::class,
        'entered' => EnteredEvent::class,
        'completed' => CompletedEvent::class,
        'announce' => AnnounceEvent::class,
    ];

    protected $dispatcher;

    private $plainEvents;

    /**
     * The workflow this adapter dispatches events for.
     *
     * Symfony events carry their own workflow, but the getter for it is
     * deprecated since Symfony 7.3. Holding the instance here lets us hand it
     * to the translated event instead of looking it up again.
     *
     * @var WorkflowInterface|null
     */
    private $workflow;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->plainEvents = array_map(function ($event) {
            return "workflow.{$event}";
        }, array_keys(static::EVENT_MAP));
    }

    /**
     * Sets the workflow this adapter dispatches events for
     *
     * @return void
     */
    public function setWorkflow(WorkflowInterface $workflow)
    {
        $this->workflow = $workflow;
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param object      $event     The event to pass to the event handlers/listeners
     * @param string|null $eventName The name of the event to dispatch. If not supplied,
     *                               the class of $event should be used instead.
     *
     * @return object The passed $event MUST be returned
     */
    public function dispatch(object $event, ?string $eventName = null): object
    {
        $name = is_null($eventName) ? get_class($event) : $eventName;

        $eventToDispatch = $this->translateEvent($eventName, $event);

        // Only dispatch the class event once
        if ($this->shouldDispatchPlainClassEvent($eventName)) {
            $this->dispatcher->dispatch($eventToDispatch);
        }

        // Dispatch with the Symfony dot syntax event names
        $this->dispatcher->dispatch($name, $eventToDispatch);

        return $eventToDispatch;
    }

    private function shouldDispatchPlainClassEvent(?string $eventName = null)
    {
        if (! $eventName) {
            return false;
        }

        return in_array($eventName, $this->plainEvents);
    }

    private function translateEvent(?string $eventName, object $symfonyEvent): object
    {
        if (is_null($eventName)) {
            return WorkflowEvent::newFromBase($symfonyEvent, $this->workflow);
        }

        $event = $this->parseWorkflowEventFromEventName($eventName);

        if (! $event) {
            return WorkflowEvent::newFromBase($symfonyEvent, $this->workflow);
        }

        $translatedEventClass = static::EVENT_MAP[$event];

        return $translatedEventClass::newFromBase($symfonyEvent, $this->workflow);
    }

    private function parseWorkflowEventFromEventName(string $eventName)
    {
        $eventSearch = preg_match('/\.(?P<event>' . implode('|', array_keys(static::EVENT_MAP)) . ')(\.|$)/i', $eventName, $eventMatches);

        if (! $eventSearch) {
            // no results or error
            return false;
        }
        $event = $eventMatches['event'] ?? false;

        if (! array_key_exists($event, static::EVENT_MAP)) {
            // fallback for no mapped event known
            return false;
        }

        return $event;
    }
}
