<?php
namespace Tests;

use ReflectionProperty;
use Tests\Fixtures\TestObject;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\TestCustomObject;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\StateMachine;
use ZeroDaHero\LaravelWorkflow\WorkflowRegistry;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use ZeroDaHero\LaravelWorkflow\MarkingStores\EloquentMarkingStore;
use Illuminate\Support\Facades\Event;

class WorkflowRegistryTest extends BaseWorkflowTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    public function testIfWorkflowIsRegistered()
    {
        $config = [
            'straight' => [
                'supports' => ['Tests\Fixtures\TestObject'],
                'places' => ['a', 'b', 'c'],
                'transitions' => [
                    't1' => [
                        'from' => 'a',
                        'to' => 'b',
                    ],
                    't2' => [
                        'from' => 'b',
                        'to' => 'c',
                    ]
                ],
            ]
        ];

        $registry = new WorkflowRegistry($config);
        $subject = new TestObject;
        $workflow = $registry->get($subject);

        $markingStoreProp = new ReflectionProperty(Workflow::class, 'markingStore');
        $markingStoreProp->setAccessible(true);

        $markingStore = $markingStoreProp->getValue($workflow);

        $this->assertInstanceof(Workflow::class, $workflow);
        $this->assertInstanceof(EloquentMarkingStore::class, $markingStore);
    }

    public function testIfStateMachineIsRegistered()
    {
        $config = [
            'straight' => [
                'type' => 'state_machine',
                'marking_store' => [
                    'type' => 'multiple_state',
                ],
                'supports' => ['Tests\Fixtures\TestObject'],
                'places' => ['a', 'b', 'c'],
                'transitions' => [
                    't1' => [
                        'from' => 'a',
                        'to' => 'b',
                    ],
                    't2' => [
                        'from' => 'b',
                        'to' => 'c',
                    ]
                ],
            ]
        ];

        $registry = new WorkflowRegistry($config);
        $subject = new TestObject;
        $workflow = $registry->get($subject);

        $markingStoreProp = new ReflectionProperty(Workflow::class, 'markingStore');
        $markingStoreProp->setAccessible(true);

        $markingStore = $markingStoreProp->getValue($workflow);

        $this->assertInstanceOf(StateMachine::class, $workflow);
        $this->assertInstanceOf(EloquentMarkingStore::class, $markingStore);
    }

    public function testEloquentMarkingStoreIsRegistered()
    {
        $config = [
            'straight' => [
                'type' => 'state_machine',
                'marking_store' => [
                    'type' => 'multiple_state',
                    'class' => MethodMarkingStore::class,
                ],
                'supports' => ['Tests\Fixtures\TestObject'],
                'places' => ['a', 'b', 'c'],
                'transitions' => [
                    't1' => [
                        'from' => 'a',
                        'to' => 'b',
                    ],
                    't2' => [
                        'from' => 'b',
                        'to' => 'c',
                    ]
                ],
            ]
        ];

        $registry = new WorkflowRegistry($config);
        $subject = new TestObject;
        $workflow = $registry->get($subject);

        $markingStoreProp = new ReflectionProperty(Workflow::class, 'markingStore');
        $markingStoreProp->setAccessible(true);

        $markingStore = $markingStoreProp->getValue($workflow);

        $this->assertInstanceOf(StateMachine::class, $workflow);
        $this->assertInstanceOf(MethodMarkingStore::class, $markingStore);
    }

    public function testIfTransitionsWithSameNameCanBothBeUsed()
    {
        $config = [
            'straight' => [
                'type' => 'state_machine',
                'supports' => ['Tests\Fixtures\TestObject'],
                'places' => ['a', 'b', 'c'],
                'transitions' => [
                    [
                        'name' => 't1',
                        'from' => 'a',
                        'to' => 'b',
                    ],
                    [
                        'name' => 't1',
                        'from' => 'c',
                        'to' => 'b',
                    ],
                    [
                        'name' => 't2',
                        'from' => 'b',
                        'to' => 'c',
                    ]
                ],
            ]
        ];

        $registry = new WorkflowRegistry($config);
        $subject = new TestObject;
        $workflow = $registry->get($subject);

        $markingStoreProp = new ReflectionProperty(Workflow::class, 'markingStore');
        $markingStoreProp->setAccessible(true);

        $markingStore = $markingStoreProp->getValue($workflow);

        $this->assertInstanceof(StateMachine::class, $workflow);
        $this->assertInstanceof(EloquentMarkingStore::class, $markingStore);
        $this->assertTrue($workflow->can($subject, 't1'));

        $workflow->apply($subject, 't1');
        $workflow->apply($subject, 't2');

        $this->assertTrue($workflow->can($subject, 't1'));
    }

    public function testWhenMultipleFromIsUsed()
    {
        $config = [
            'straight' => [
                'type'=> 'state_machine',
                'supports' => ['Tests\Fixtures\TestObject'],
                'places' => ['a', 'b', 'c'],
                'transitions' => [
                    [
                        'name' => 't1',
                        'from' => 'a',
                        'to' => 'b',
                    ],
                    [
                        'name' => 't2',
                        'from' => [
                            'a',
                            'b'
                        ],
                        'to' => 'c',
                    ],
                ],
            ],
        ];

        $registry = new WorkflowRegistry($config);
        $subject = new TestObject;
        $workflow = $registry->get($subject);

        $markingStoreProp = new ReflectionProperty(Workflow::class, 'markingStore');
        $markingStoreProp->setAccessible(true);

        $markingStore = $markingStoreProp->getValue($workflow);

        $this->assertInstanceof(StateMachine::class, $workflow);
        $this->assertInstanceof(EloquentMarkingStore::class, $markingStore);
        $this->assertTrue($workflow->can($subject, 't1'));
        $this->assertTrue($workflow->can($subject, 't2'));
    }

    public function testIfInitialPlaceIsRegistered()
    {
        $config = [
            'straight' => [
                'supports' => ['Tests\Fixtures\TestObject'],
                'places' => ['a', 'b', 'c'],
                'transitions' => [
                    't1' => [
                        'from' => 'c',
                        'to' => 'b',
                    ],
                    't2' => [
                        'from' => 'b',
                        'to' => 'a',
                    ]
                ],
                'initial_places' => 'c'
            ]
        ];

        $registry = new WorkflowRegistry($config);
        $subject = new TestObject;
        $workflow = $registry->get($subject);

        $markingStoreProp = new ReflectionProperty(Workflow::class, 'markingStore');
        $markingStoreProp->setAccessible(true);

        $markingStore = $markingStoreProp->getValue($workflow);

        $this->assertInstanceof(Workflow::class, $workflow);
        $this->assertInstanceof(EloquentMarkingStore::class, $markingStore);

        $this->assertEquals(['c'], $workflow->getDefinition()->getInitialPlaces());
    }

    public function testIfCustomMarkingPropertyIsUsed()
    {
        $config = [
            'straight' => [
                'supports' => ['Tests\Fixtures\TestCustomObject'],
                'places' => ['a', 'b', 'c'],
                'marking_store' => [
                    'type' => 'single_state',
                    'property' => 'state'
                ],
                'transitions' => [
                    't1' => [
                        'from' => 'c',
                        'to' => 'b',
                    ],
                    't2' => [
                        'from' => 'b',
                        'to' => 'a',
                    ]
                ],
                'initial_places' => 'c'
            ]
        ];

        $registry = new WorkflowRegistry($config);
        $subject = new TestCustomObject;
        $workflow = $registry->get($subject);

        $markingStoreProp = new ReflectionProperty(Workflow::class, 'markingStore');
        $markingStoreProp->setAccessible(true);

        $markingStore = $markingStoreProp->getValue($workflow);

        $this->assertInstanceof(Workflow::class, $workflow);
        $this->assertInstanceof(EloquentMarkingStore::class, $markingStore);
        $this->assertTrue($workflow->can($subject, 't1'));

        $workflow->apply($subject, 't1');

        $this->assertEquals('b', $subject->getState());
    }
}
