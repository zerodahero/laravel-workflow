<?php

namespace Tests;

use ReflectionException;
use Tests\Fixtures\TestCustomObject;
use Symfony\Component\Workflow\TransitionBlocker;
use ZeroDaHero\LaravelWorkflow\Events\GuardEvent;
use ZeroDaHero\LaravelWorkflow\WorkflowRegistry;
use Illuminate\Support\Facades\Event;
use Workflow;

/**
 * @group integration
 */
class GuardEventTest extends BaseWorkflowTestCase
{

    public const MESSAGE_ERROR = 'The transition is blocked';

    /**
     * @throws ReflectionException
     */
    public function testTransitionIsOpen(): void
    {
        $registry = new WorkflowRegistry($this->getConfig());
        $subject = new TestCustomObject;
        $workflow = $registry->get($subject);

        $this->assertEquals(true, $workflow->can($subject, 't1'));
    }

    /**
     * @throws ReflectionException
     */
    public function testSetBlocking(): void
    {
        $registry = new WorkflowRegistry($this->getConfig());
        $subject = new TestCustomObject;
        $workflow = $registry->get($subject);

        Event::listen('workflow.straight.guard.t1', function (GuardEvent $event) {
            $event->setBlocked(true, self::MESSAGE_ERROR);
            $this->assertEquals(true, $event->isBlocked());
        });

        $this->assertEquals(false, $workflow->can($subject, 't1'));
    }


    /**
     * @throws ReflectionException
     */
    public function testAddTransitionBlocker(): void
    {
        $registry = new WorkflowRegistry($this->getConfig());
        $subject = new TestCustomObject;
        $workflow = $registry->get($subject);

        Event::listen('workflow.straight.guard.t1', function (GuardEvent $event) {
            $event->addTransitionBlocker(new TransitionBlocker(self::MESSAGE_ERROR, 0));
            $this->assertCount(1, $event->getTransitionBlockerList());
        });

        $this->assertEquals(false, $workflow->can($subject, 't1'));
    }


    /**
     * Define environment setup.
     *
     * @return array
     */
    protected function getConfig(): array
    {
        return [
            'straight' => [
                'supports' => [TestCustomObject::class],
                'places' => ['a', 'b'],
                'marking_store' => [
                    'type' => 'single_state',
                    'property' => 'state',
                ],
                'transitions' => [
                    't1' => [
                        'from' => 'a',
                        'to' => 'b',
                    ],
                ],
                'initial_places' => 'a',
            ],
        ];
    }
}
