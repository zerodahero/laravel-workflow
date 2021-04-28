<?php

namespace ZeroDaHero\LaravelWorkflow\Events;

use Illuminate\Contracts\Events\Dispatcher;
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

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
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
        $this->dispatcher->dispatch($eventToDispatch);
        $this->dispatcher->dispatch($name, $eventToDispatch);

        return $eventToDispatch;
    }

    private function translateEvent(?string $eventName, object $symfonyEvent): object
    {
        if (is_null($eventName)) {
            return new UnknownEvent($symfonyEvent);
        }

        $eventNameParts = explode('.', $eventName);

        if (count($eventNameParts) === 2) {
            $event = $eventNameParts[1];
        } elseif (count($eventNameParts) >= 3) {
            $event = $eventNameParts[2];
        } else {
            // fallback if unknown event name
            return new UnknownEvent($symfonyEvent);
        }

        if (! array_key_exists($event, static::EVENT_MAP)) {
            // fallback for no mapped event known
            return new UnknownEvent($symfonyEvent);
        }

        $translatedEventClass = static::EVENT_MAP[$event];

        return new $translatedEventClass($symfonyEvent);
    }
}
