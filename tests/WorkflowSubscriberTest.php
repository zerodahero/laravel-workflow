<?php

namespace Tests;

use Illuminate\Support\Facades\Event;
use Symfony\Component\Workflow\WorkflowEvents;
use Tests\Fixtures\TestObject;
use ZeroDaHero\LaravelWorkflow\Events\AnnounceEvent;
use ZeroDaHero\LaravelWorkflow\Events\CompletedEvent;
use ZeroDaHero\LaravelWorkflow\Events\EnteredEvent;
use ZeroDaHero\LaravelWorkflow\Events\EnterEvent;
use ZeroDaHero\LaravelWorkflow\Events\GuardEvent;
use ZeroDaHero\LaravelWorkflow\Events\LeaveEvent;
use ZeroDaHero\LaravelWorkflow\Events\TransitionEvent;
use ZeroDaHero\LaravelWorkflow\WorkflowRegistry;

class WorkflowSubscriberTest extends BaseWorkflowTestCase
{
    private $eventSets = [
        'workflow_enter' => [
            EnteredEvent::class,
            'workflow.entered',
            'workflow.%s.entered',
        ],
        'guard' => [
            GuardEvent::class,
            'workflow.guard',
            'workflow.%s.guard',
            'workflow.%s.guard.%s',
        ],
        'leave' => [
            LeaveEvent::class,
            'workflow.leave',
            'workflow.%s.leave',
            'workflow.%s.leave.%s',
        ],
        'transition' => [
            TransitionEvent::class,
            'workflow.transition',
            'workflow.%s.transition',
            'workflow.%s.transition.%s',
        ],
        'enter' => [
            EnterEvent::class,
            'workflow.enter',
            'workflow.%s.enter',
            'workflow.%s.enter.%s',
        ],
        'entered' => [
            EnteredEvent::class,
            'workflow.entered',
            'workflow.%s.entered',
            'workflow.%s.entered.%s',
        ],
        'completed' => [
            CompletedEvent::class,
            'workflow.completed',
            'workflow.%s.completed',
            'workflow.%s.completed.%s',
        ],
        'announce' => [
            AnnounceEvent::class,
            'workflow.announce',
            'workflow.%s.announce',
            'workflow.%s.announce.%s',
        ],
    ];

    /**
     * @test
     */
    public function testIfWorkflowEmitsEvents()
    {
        Event::fake();

        $config = [
            'straight' => [
                'supports' => [TestObject::class],
                'places' => ['a', 'b', 'c'],
                'transitions' => [
                    't1' => [
                        'from' => 'a',
                        'to' => 'b',
                    ],
                    't2' => [
                        'from' => 'b',
                        'to' => 'c',
                    ],
                ],
            ],
        ];

        $registry = new WorkflowRegistry($config);
        $object = new TestObject();
        $workflow = $registry->get($object);

        $workflow->apply($object, 't1');

        // Symfony Workflow 4.2.9 fires entered event on initialize
        $this->assertEventSetDispatched('workflow_enter');

        $this->assertEventSetDispatched('guard', 't1');

        $this->assertEventSetDispatched('leave', 'a');

        $this->assertEventSetDispatched('transition', 't1');

        $this->assertEventSetDispatched('enter', 'b');

        $this->assertEventSetDispatched('entered', 'b');

        $this->assertEventSetDispatched('completed', 't1');

        // Announce happens after completed
        $this->assertEventSetDispatched('announce', 't1');

        $this->assertEventSetDispatched('guard', 't2');
    }

    /**
     * @test
     * @dataProvider providesEventsToDispatchScenarios
     */
    public function testIfWorkflowOnlyEmitsSpecificEvents(?array $eventsToDispatch, array $eventsToExpect)
    {
        Event::fake();

        $config = [
            'straight' => [
                'supports' => [TestObject::class],
                'places' => ['a', 'b', 'c'],
                'events_to_dispatch' => $eventsToDispatch,
                'transitions' => [
                    't1' => [
                        'from' => 'a',
                        'to' => 'b',
                    ],
                    't2' => [
                        'from' => 'b',
                        'to' => 'c',
                    ],
                ],
            ],
        ];

        $registry = new WorkflowRegistry($config);
        $object = new TestObject();
        $workflow = $registry->get($object);

        $workflow->apply($object, 't1');

        // Ignoring guard since it's always dispatched
        $this->assertEventSetDispatched('workflow_enter', null, in_array('entered', $eventsToExpect));
        $this->assertEventSetDispatched('leave', 'a', in_array('leave', $eventsToExpect));
        $this->assertEventSetDispatched('transition', 't1', in_array('transition', $eventsToExpect));
        $this->assertEventSetDispatched('enter', 'b', in_array('enter', $eventsToExpect));
        $this->assertEventSetDispatched('entered', 'b', in_array('entered', $eventsToExpect));
        $this->assertEventSetDispatched('completed', 't1', in_array('completed', $eventsToExpect));
        $this->assertEventSetDispatched('announce', 't1', in_array('announce', $eventsToExpect));
    }

    public function providesEventsToDispatchScenarios()
    {
        $events = [
            'enter' => WorkflowEvents::ENTER,
            'leave' => WorkflowEvents::LEAVE,
            'transition' => WorkflowEvents::TRANSITION,
            'entered' => WorkflowEvents::ENTERED,
            'completed' => WorkflowEvents::COMPLETED,
            'announce' => WorkflowEvents::ANNOUNCE,
        ];

        yield 'null events dispatches all' => [
            null, array_keys($events),
        ];

        yield 'empty events dispatches none' => [
            [], [],
        ];

        foreach ($events as $key => $event) {
            yield "silences ${event}" => [[$event], [$key]];
        }
    }

    private function assertEventSetDispatched(string $eventSet, ?string $arg = null, bool $expected = true)
    {
        $workflow = 'straight';

        $method = ($expected) ? 'assertDispatched' : 'assertNotDispatched';

        foreach ($this->eventSets[$eventSet] as $event) {
            Event::$method(sprintf($event, $workflow, $arg));
        }
    }
}
