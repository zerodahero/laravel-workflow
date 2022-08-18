<?php

namespace Tests;

use Mockery;
use stdClass;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\CanAccessProtected;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\Transition;
use Illuminate\Contracts\Events\Dispatcher;
use ZeroDaHero\LaravelWorkflow\Events\BaseEvent;
use Symfony\Component\Workflow\WorkflowInterface;
use ZeroDaHero\LaravelWorkflow\Events\EnterEvent;
use ZeroDaHero\LaravelWorkflow\Events\GuardEvent;
use ZeroDaHero\LaravelWorkflow\Events\LeaveEvent;
use ZeroDaHero\LaravelWorkflow\Events\EnteredEvent;
use ZeroDaHero\LaravelWorkflow\Events\AnnounceEvent;
use ZeroDaHero\LaravelWorkflow\Events\WorkflowEvent;
use ZeroDaHero\LaravelWorkflow\Events\CompletedEvent;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ZeroDaHero\LaravelWorkflow\Events\TransitionEvent;
use ZeroDaHero\LaravelWorkflow\Events\DispatcherAdapter;

class DispatchAdapterTest extends TestCase
{
    use CanAccessProtected;
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @dataProvider providesEventScenarios
     *
     * @param mixed $expectedEvent
     * @param mixed $event
     * @param mixed $eventName
     * @param mixed $expectedPackageEvent
     * @param mixed $symfonyEvent
     * @param mixed $eventDotName
     * @param mixed $expectedDotName
     */
    public function testAdaptsSymfonyEventsToLaravel($expectedPackageEvent, $symfonyEvent, $eventDotName, $expectedDotName)
    {
        $mockDispatcher = Mockery::mock(Dispatcher::class);

        if (in_array($eventDotName, [
            'workflow.guard',
            'workflow.leave',
            'workflow.transition',
            'workflow.enter',
            'workflow.entered',
            'workflow.completed',
            'workflow.announce',
        ])) {
            $mockDispatcher->shouldReceive('dispatch')
                ->once()
                ->with($expectedPackageEvent);
        }
        $mockDispatcher->shouldReceive('dispatch')
            ->once()
            ->with($expectedDotName, $expectedPackageEvent);
        $adapter = new DispatcherAdapter($mockDispatcher);

        $event = $adapter->dispatch($symfonyEvent, $eventDotName);
        $this->assertInstanceOf($expectedPackageEvent, $event);

        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function providesEventScenarios()
    {
        $faker = \Faker\Factory::create();

        $dispatcher = new DispatcherAdapter(Mockery::mock(Dispatcher::class));
        $eventList = $this->getProtectedConstant($dispatcher, 'EVENT_MAP');
        $mockWorkflow = Mockery::mock(WorkflowInterface::class);

        $reverseMap = [
            GuardEvent::class => \Symfony\Component\Workflow\Event\GuardEvent::class,
            LeaveEvent::class => \Symfony\Component\Workflow\Event\LeaveEvent::class,
            TransitionEvent::class => \Symfony\Component\Workflow\Event\TransitionEvent::class,
            EnterEvent::class => \Symfony\Component\Workflow\Event\EnterEvent::class,
            EnteredEvent::class => \Symfony\Component\Workflow\Event\EnteredEvent::class,
            CompletedEvent::class => \Symfony\Component\Workflow\Event\CompletedEvent::class,
            AnnounceEvent::class => \Symfony\Component\Workflow\Event\AnnounceEvent::class,
        ];

        foreach ([
            'no dots' => ['', ''],
            'transition dot' => ['', '.'],
            'name dot' => ['.', ''],
            'both dot' => ['.','.'],
        ] as $dotScenario => [$nameSeparator, $transitionSeparator]) {
            foreach ($eventList as $eventType => $expectedEventClass) {
                // Cover scenarios with '.' in the workflow name or transition name
                $transition = implode($transitionSeparator, $faker->words(3));
                $name = implode($nameSeparator, $faker->words(3));

                $symfonyEvent = $reverseMap[$expectedEventClass];
                $symfonyEvent = new $symfonyEvent(new stdClass(), new Marking(), new Transition($transition, [], []), $mockWorkflow);

                foreach ([
                    "workflow.${eventType}",
                    "workflow.${name}.${eventType}",
                    "workflow.${name}.${eventType}.${transition}",
                ] as $eventName) {
                    yield "${eventName} (${dotScenario})" => [
                        $expectedEventClass,
                        $symfonyEvent,
                        $eventName,
                        $eventName,
                    ];
                }

                yield "No event name ${eventType} (${dotScenario})" => [
                    WorkflowEvent::class,
                    $symfonyEvent,
                    null,
                    get_class($symfonyEvent),
                ];
            }
        }
    }
}
