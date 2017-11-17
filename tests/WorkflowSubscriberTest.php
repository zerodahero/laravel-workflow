<?php
namespace Tests {

    use PHPUnit\Framework\TestCase;
    use Brexis\LaravelWorkflow\WorkflowRegistry;
    use Tests\Fixtures\TestObject;
    use Illuminate\Support\Facades\Event;

    class WorkflowSubscriberTest extends TestCase
    {
        public function testIfWorkflowIsRegisrter()
        {
            global $events;

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

            $this->assertTrue($events[0] == "workflow.straight.guard");
            $this->assertTrue($events[1] == "workflow.straight.leave");
            $this->assertTrue($events[2] == "workflow.straight.transition");
            $this->assertTrue($events[3] == "workflow.straight.enter");
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
