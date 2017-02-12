<?php
use PHPUnit\Framework\TestCase;
use Brexis\LaravelWorkflow\WorkflowRegistry;
use Symfony\Component\Workflow\MarkingStore\MultipleStateMarkingStore;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\StateMachine;

class WorkflowRegistryTest extends TestCase
{
    public function testIfWorkflowIsRegisrter()
    {
        $config = [
            'straight'   => [
                'supports'      => ['stdClass'],
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

        $registry = new WorkflowRegistry($config);
        $object = new stdClass;
        $workflow = $registry->get($object);

        $markingProp = new \ReflectionProperty(Workflow::class, 'markingStore');
        $markingProp->setAccessible(true);

        $marking = $markingProp->getValue($workflow);

        $this->assertTrue($workflow instanceof Workflow);
        $this->assertTrue($marking instanceof SingleStateMarkingStore);
    }

    public function testIfStateMachineIsRegisrter()
    {
        $config = [
            'straight'   => [
                'type'          => 'state_machine',
                'marking_store' => [
                    'type' => 'multiple_state',
                ],
                'supports'      => ['stdClass'],
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

        $registry = new WorkflowRegistry($config);
        $object = new stdClass;
        $workflow = $registry->get($object);

        $markingProp = new \ReflectionProperty(Workflow::class, 'markingStore');
        $markingProp->setAccessible(true);

        $marking = $markingProp->getValue($workflow);

        $this->assertTrue($workflow instanceof StateMachine);
        $this->assertTrue($marking instanceof MultipleStateMarkingStore);
    }
}
