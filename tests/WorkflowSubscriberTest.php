<?php
namespace Tests {

    use PHPUnit\Framework\TestCase;
    use Brexis\LaravelWorkflow\WorkflowRegistry;
    use Tests\Fixtures\TestObject;

    class WorkflowSubscriberTest extends TestCase
    {
        public function testIfWorkflowEmitsEvents()
        {
            global $events;

            $events = [];

            $config     = [
            'straight'   => [
                'supports'      => ['Tests\Fixtures\TestObject'],
                'places'        => ['a', 'b', 'c'],
                'transitions'   => [
                    't1' => [
                        'from' => 'a',
                        'to'   => 'b',
                    ],
                    't2' => [
                        'from' => 'b',
                        'to'   => 'c',
                    ]
                ],
            ]
            ];

            $registry   = new WorkflowRegistry($config);
            $object     = new TestObject;
            $workflow   = $registry->get($object);

            $workflow->apply($object, 't1');

            $this->assertTrue($events[0] instanceof \Brexis\LaravelWorkflow\Events\GuardEvent);
            $this->assertTrue($events[1] == "workflow.straight.guard");
            $this->assertTrue($events[2] instanceof \Brexis\LaravelWorkflow\Events\LeaveEvent);
            $this->assertTrue($events[3] == "workflow.straight.leave");
            $this->assertTrue($events[4] instanceof \Brexis\LaravelWorkflow\Events\TransitionEvent);
            $this->assertTrue($events[5] == "workflow.straight.transition");
            $this->assertTrue($events[6] instanceof \Brexis\LaravelWorkflow\Events\EnterEvent);
            $this->assertTrue($events[7] == "workflow.straight.enter");
            $this->assertTrue($events[8] instanceof \Brexis\LaravelWorkflow\Events\EnteredEvent);
            $this->assertTrue($events[9] == "workflow.straight.entered");
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
