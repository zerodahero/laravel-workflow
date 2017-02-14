<?php
namespace Tests {

    use PHPUnit\Framework\TestCase;
    use Brexis\LaravelWorkflow\WorkflowRegistry;
    use Tests\Fixtures\TestObject;

    class WorkflowRegistryTest extends TestCase
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

            $this->assertTrue($events[0] instanceof \Brexis\LaravelWorkflow\Events\Guard);
            $this->assertTrue($events[1] instanceof \Brexis\LaravelWorkflow\Events\Leave);
            $this->assertTrue($events[2] instanceof \Brexis\LaravelWorkflow\Events\Transition);
            $this->assertTrue($events[3] instanceof \Brexis\LaravelWorkflow\Events\Enter);
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
