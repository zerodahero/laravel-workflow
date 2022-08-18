<?php

namespace Tests;

use Event;
use Workflow;
use Tests\Fixtures\TestModel;
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\TestEloquentModel;
use Tests\Fixtures\TestWorkflowListener;
use ZeroDaHero\LaravelWorkflow\Events\GuardEvent;
use Symfony\Component\Workflow\TransitionBlockerList;
use ZeroDaHero\LaravelWorkflow\Events\TransitionEvent;
use ZeroDaHero\LaravelWorkflow\Facades\WorkflowFacade;
use ZeroDaHero\LaravelWorkflow\WorkflowServiceProvider;
use Symfony\Component\Workflow\Event\GuardEvent as SymfonyGuardEvent;

/**
 * @group integration
 */
class EventTest extends TestCase
{
    /**
     * @test
     */
    public function testSerializesAndUnserializes()
    {
        $subject = new TestModel();
        $baseEvent = new \Symfony\Component\Workflow\Event\Event(
            $subject,
            new \Symfony\Component\Workflow\Marking(['here' => 1]),
            new \Symfony\Component\Workflow\Transition('transition_name', 'here', 'there'),
            Workflow::get($subject, 'straight')
        );
        $event = TransitionEvent::newFromBase($baseEvent);
        $serialized = serialize($event);

        $this->assertIsString($serialized);

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(TransitionEvent::class, $unserialized);

        $this->assertEquals($subject, $unserialized->getSubject());
    }

    /**
     * @test
     */
    public function testGuardEventSerializesAndUnserializes()
    {
        $subject = new TestModel();
        $event = new GuardEvent(
            $subject,
            new \Symfony\Component\Workflow\Marking(['here' => 1]),
            new \Symfony\Component\Workflow\Transition('transition_name', 'here', 'there'),
            Workflow::get($subject, 'straight')
        );
        $serialized = serialize($event);

        $this->assertIsString($serialized);

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(GuardEvent::class, $unserialized);

        // Attempt a proxy method
        $this->assertInstanceOf(TransitionBlockerList::class, $unserialized->getTransitionBlockerList());
    }

    /**
     * @test
     */
    public function testGuardEventGuards()
    {
        $subject = new TestModel();
        $symfonyEvent = new SymfonyGuardEvent(
            $subject,
            new \Symfony\Component\Workflow\Marking(['here' => 1]),
            new \Symfony\Component\Workflow\Transition('transition_name', 'here', 'there'),
            Workflow::get($subject, 'straight')
        );

        $event = GuardEvent::newFromBase($symfonyEvent);

        $event->setBlocked(true);

        $this->assertTrue($event->isBlocked());

        $blockerList = $event->getTransitionBlockerList();
        $this->assertInstanceOf(TransitionBlockerList::class, $blockerList);
        $this->assertFalse($blockerList->isEmpty());
    }

    /**
     * @test
     */
    public function testQueueableEvents()
    {
        Event::listen('workflow.straight.test.transition.to_there', [TestWorkflowListener::class, 'handle']);
        $subject = app(TestEloquentModel::class);
        $workflow = Workflow::get($subject, 'straight.test');
        $this->assertTrue($subject->workflow_can('to_there', 'straight.test'));
        $subject->workflow_apply('to_there', 'straight.test');
        $this->assertEquals('there', $subject->marking);
    }

    protected function getPackageProviders($app)
    {
        return [WorkflowServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Workflow' => WorkflowFacade::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']['workflow'] = [
            'straight' => [
                'type' => 'workflow',
                'marking_store' => [
                    'type' => 'single_state',
                ],
                'supports' => [
                    TestModel::class,
                ],
                'places' => ['here', 'there', 'somewhere'],
                'transitions' => [
                    'to_there' => [
                        'from' => 'here',
                        'to' => 'there',
                    ],
                    'to_somewhere' => [
                        'from' => 'there',
                        'to' => 'somewhere',
                    ],
                ],
            ],
            'straight.test' => [
                'type' => 'workflow',
                'marking_store' => [
                    'type' => 'single_state',
                ],
                'supports' => [
                    TestEloquentModel::class,
                ],
                'places' => ['here', 'there', 'somewhere'],
                'transitions' => [
                    'to_there' => [
                        'from' => 'here',
                        'to' => 'there',
                    ],
                    'to_somewhere' => [
                        'from' => 'there',
                        'to' => 'somewhere',
                    ],
                ],
            ],
        ];
    }
}
