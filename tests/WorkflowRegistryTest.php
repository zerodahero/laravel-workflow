<?php

namespace Tests;

use Illuminate\Support\Facades\Event;
use ReflectionProperty;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Workflow;
use Tests\Fixtures\TestCustomObject;
use Tests\Fixtures\TestObject;
use Tests\Helpers\CanAccessProtected;
use ZeroDaHero\LaravelWorkflow\MarkingStores\EloquentMarkingStore;
use ZeroDaHero\LaravelWorkflow\WorkflowRegistry;

class WorkflowRegistryTest extends BaseWorkflowTestCase
{
    use CanAccessProtected;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    /**
    * @test
    */
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

        $this->assertInstanceof(Workflow::class, $workflow);
        $this->assertInstanceof(EloquentMarkingStore::class, $markingStore);
    }

    /**
     * @test
     */
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

        $this->assertInstanceOf(StateMachine::class, $workflow);
        $this->assertInstanceOf(EloquentMarkingStore::class, $markingStore);
    }

    /**
     * @test
     */
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

        $this->assertInstanceOf(StateMachine::class, $workflow);
        $this->assertInstanceOf(MethodMarkingStore::class, $markingStore);
    }

    /**
     * @test
     */
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

        $workflow->apply($subject, 't1');
        $workflow->apply($subject, 't2');

        $this->assertTrue($workflow->can($subject, 't1'));
    }

    /**
     * @test
     */
    public function testWhenMultipleFromIsUsed()
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
                        'name' => 't2',
                        'from' => [
                            'a',
                            'b',
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

    /**
     * @test
     */
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
                    ],
                ],
                'initial_places' => 'c',
            ],
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

    /**
     * @test
     */
    public function testIfCustomMarkingPropertyIsUsed()
    {
        $config = [
            'straight' => [
                'supports' => ['Tests\Fixtures\TestCustomObject'],
                'places' => ['a', 'b', 'c'],
                'marking_store' => [
                    'type' => 'single_state',
                    'property' => 'state',
                ],
                'transitions' => [
                    't1' => [
                        'from' => 'c',
                        'to' => 'b',
                    ],
                    't2' => [
                        'from' => 'b',
                        'to' => 'a',
                    ],
                ],
                'initial_places' => 'c',
            ],
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

    /**
     * @test
     * @dataProvider providesAutomaticMarkingStoreScenarios
     */
    public function testIfMarkingStoreIsAutomatic(array $typeConfig, bool $expectSingleState)
    {
        $config = [
            'test' => array_merge([
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
                    ],
                ],
            ], $typeConfig),
        ];

        $registry = new WorkflowRegistry($config);
        $subject = new TestObject;
        $workflow = $registry->get($subject);

        $markingStoreProp = new ReflectionProperty(Workflow::class, 'markingStore');
        $markingStoreProp->setAccessible(true);

        $markingStore = $markingStoreProp->getValue($workflow);

        $this->assertInstanceof(Workflow::class, $workflow);
        $this->assertInstanceof(EloquentMarkingStore::class, $markingStore);
        $this->assertEquals($expectSingleState, $this->getProtectedProperty($markingStore, 'singleState'));
    }

    public function providesAutomaticMarkingStoreScenarios()
    {
        return [
            'default workflow, default multi' => [[], false],
            'set workflow, default multi' => [[
                'type' => 'workflow',
            ], false],
            'set workflow, override single' => [[
                'type' => 'workflow',
                'marking_store' => [
                    'type' => 'single_state',
                ],
            ], true],
            'set workflow, override multiple' => [[
                'type' => 'workflow',
                'marking_store' => [
                    'type' => 'multiple_state',
                ],
            ], false],
            'set state machine, default single' => [[
                'type' => 'state_machine',
            ], true],
            'set state machine, override multi' => [[
                'type' => 'state_machine',
                'marking_store' => [
                    'type' => 'multiple_state',
                ],
            ], false],
            'set state machine, override single' => [[
                'type' => 'state_machine',
                'marking_store' => [
                    'type' => 'single_state',
                ],
            ], true],
        ];
    }
}
