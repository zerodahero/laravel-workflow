<?php

namespace Tests;

use Tests\Fixtures\TestObject;
use Illuminate\Support\Facades\Event;
use ZeroDaHero\LaravelWorkflow\WorkflowRegistry;
use ZeroDaHero\LaravelWorkflow\Events\EnterEvent;
use ZeroDaHero\LaravelWorkflow\Events\GuardEvent;
use ZeroDaHero\LaravelWorkflow\Events\LeaveEvent;
use ZeroDaHero\LaravelWorkflow\Events\EnteredEvent;
use ZeroDaHero\LaravelWorkflow\Events\AnnounceEvent;
use ZeroDaHero\LaravelWorkflow\Events\CompletedEvent;
use ZeroDaHero\LaravelWorkflow\Events\TransitionEvent;

class WorkflowSubscriberTest extends BaseWorkflowTestCase
{
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
        Event::assertDispatched(EnteredEvent::class);
        Event::assertDispatched('workflow.entered');
        Event::assertDispatched('workflow.straight.entered');

        Event::assertDispatched(GuardEvent::class);
        Event::assertDispatched('workflow.guard');
        Event::assertDispatched('workflow.straight.guard');
        Event::assertDispatched('workflow.straight.guard.t1');

        Event::assertDispatched(LeaveEvent::class);
        Event::assertDispatched('workflow.leave');
        Event::assertDispatched('workflow.straight.leave');
        Event::assertDispatched('workflow.straight.leave.a');

        Event::assertDispatched(TransitionEvent::class);
        Event::assertDispatched('workflow.transition');
        Event::assertDispatched('workflow.straight.transition');
        Event::assertDispatched('workflow.straight.transition.t1');

        Event::assertDispatched(EnterEvent::class);
        Event::assertDispatched('workflow.enter');
        Event::assertDispatched('workflow.straight.enter');
        Event::assertDispatched('workflow.straight.enter.b');

        Event::assertDispatched(EnteredEvent::class);
        Event::assertDispatched('workflow.entered');
        Event::assertDispatched('workflow.straight.entered');
        Event::assertDispatched('workflow.straight.entered.b');

        Event::assertDispatched(CompletedEvent::class);
        Event::assertDispatched('workflow.completed');
        Event::assertDispatched('workflow.straight.completed');
        Event::assertDispatched('workflow.straight.completed.t1');

        // Announce happens after completed
        Event::assertDispatched(AnnounceEvent::class);
        Event::assertDispatched('workflow.announce');
        Event::assertDispatched('workflow.straight.announce');
        Event::assertDispatched('workflow.straight.announce.t1');

        Event::assertDispatched(GuardEvent::class);
        Event::assertDispatched('workflow.guard');
        Event::assertDispatched('workflow.straight.guard');
        Event::assertDispatched('workflow.straight.guard.t2');
    }
}
