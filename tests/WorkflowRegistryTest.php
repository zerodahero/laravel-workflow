<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use Brexis\LaravelWorkflow\WorkflowRegistry;
use ReflectionProperty;
use Symfony\Component\Workflow\MarkingStore\MultipleStateMarkingStore;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\StateMachine;
use Tests\Fixtures\TestObject;

class WorkflowRegistryTest extends TestCase
{
    public function testIfWorkflowIsRegistered()
    {
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
        $subject    = new TestObject;
        $workflow   = $registry->get($subject);

        $markingStoreProp = new ReflectionProperty(Workflow::class, 'markingStore');
        $markingStoreProp->setAccessible(true);

        $markingStore = $markingStoreProp->getValue($workflow);

        $this->assertTrue($workflow instanceof Workflow);
        $this->assertTrue($markingStore instanceof SingleStateMarkingStore);
    }

    public function testIfStateMachineIsRegistered()
    {
        $config     = [
            'straight'   => [
                'type'          => 'state_machine',
                'marking_store' => [
                    'type' => 'multiple_state',
                ],
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
        $subject     = new TestObject;
        $workflow   = $registry->get($subject);

        $markingStoreProp = new ReflectionProperty(Workflow::class, 'markingStore');
        $markingStoreProp->setAccessible(true);

        $markingStore = $markingStoreProp->getValue($workflow);

        $this->assertTrue($workflow instanceof StateMachine);
        $this->assertTrue($markingStore instanceof MultipleStateMarkingStore);
    }
}
