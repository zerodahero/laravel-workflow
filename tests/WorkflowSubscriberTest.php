<?php

namespace Tests {

    use Brexis\LaravelWorkflow\Events\EnteredEvent;
    use Brexis\LaravelWorkflow\Events\EnterEvent;
    use Brexis\LaravelWorkflow\Events\GuardEvent;
    use Brexis\LaravelWorkflow\Events\LeaveEvent;
    use Brexis\LaravelWorkflow\Events\TransitionEvent;
    use PHPUnit\Framework\TestCase;
    use Brexis\LaravelWorkflow\WorkflowRegistry;
    use Tests\Fixtures\TestObject;

    class WorkflowSubscriberTest extends TestCase
    {
        public function testIfWorkflowEmitsEvents()
        {
            global $events;

            $events = [];

            $config = [
                'straight' => [
                    'supports'    => [TestObject::class],
                    'places'      => ['a', 'b', 'c'],
                    'transitions' => [
                        't1' => [
                            'from' => 'a',
                            'to'   => 'b',
                        ],
                        't2' => [
                            'from' => 'b',
                            'to'   => 'c',
                        ],
                    ],
                ],
            ];

            $registry = new WorkflowRegistry($config);
            $object = new TestObject;
            $workflow = $registry->get($object);

            $workflow->apply($object, 't1');

            $this->assertInstanceOf(GuardEvent::class, $events[0]);
            $this->assertEquals('workflow.guard', $events[1]);
            $this->assertEquals('workflow.straight.guard', $events[2]);
            $this->assertEquals('workflow.straight.guard.t1', $events[3]);

            $this->assertInstanceOf(LeaveEvent::class, $events[4]);
            $this->assertEquals('workflow.leave', $events[5]);
            $this->assertEquals('workflow.straight.leave', $events[6]);
            $this->assertEquals('workflow.straight.leave.t1', $events[7]);

            $this->assertInstanceOf(TransitionEvent::class, $events[8]);
            $this->assertEquals('workflow.transition', $events[9]);
            $this->assertEquals('workflow.straight.transition', $events[10]);
            $this->assertEquals('workflow.straight.transition.t1', $events[11]);

            $this->assertInstanceOf(EnterEvent::class, $events[12]);
            $this->assertEquals('workflow.enter', $events[13]);
            $this->assertEquals('workflow.straight.enter', $events[14]);
            $this->assertEquals('workflow.straight.enter.t1', $events[15]);

            $this->assertInstanceOf(EnteredEvent::class, $events[16]);
            $this->assertEquals('workflow.entered', $events[17]);
            $this->assertEquals('workflow.straight.entered', $events[18]);
            $this->assertEquals('workflow.straight.entered.t1', $events[19]);
        }
    }
}

namespace {

    $events = null;

    function event($ev)
    {
        global $events;
        $events[] = $ev;
    }
}
