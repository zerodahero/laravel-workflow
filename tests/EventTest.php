<?php

namespace Tests;

use Orchestra\Testbench\TestCase;
use Tests\Fixtures\TestModel;
use Workflow;
use ZeroDaHero\LaravelWorkflow\Events\TransitionEvent;
use ZeroDaHero\LaravelWorkflow\Facades\WorkflowFacade;
use ZeroDaHero\LaravelWorkflow\WorkflowServiceProvider;

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
        $event = new TransitionEvent($baseEvent);
        $serialized = serialize($event);

        $this->assertIsString($serialized);

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(TransitionEvent::class, $unserialized);
        $this->assertEquals($baseEvent, $unserialized->getOriginalEvent());
    }

    /**
     * @test
     */
    public function testProxiesCalls()
    {
        $subject = new TestModel();
        $marking = new \Symfony\Component\Workflow\Marking(['here' => 1]);
        $transition = new \Symfony\Component\Workflow\Transition('transition_name', 'here', 'there');
        $workflow = Workflow::get($subject, 'straight');
        $baseEvent = new \Symfony\Component\Workflow\Event\Event(
            $subject,
            $marking,
            $transition,
            $workflow
        );
        $event = new TransitionEvent($baseEvent);

        $this->assertEquals($marking, $event->getMarking());
        $this->assertEquals($subject, $event->getSubject());
        $this->assertEquals($transition, $event->getTransition());
        $this->assertEquals($workflow, $event->getWorkflow());
        $this->assertEquals('straight', $event->getWorkflowName());
        // FUTURE
        // $this->assertEquals(??, $event->getMetadata(string $key, $subject));

        $this->assertNull($event->doSomethingUndefined());
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
                'supports' => ['Tests\Fixtures\TestModel'],
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
