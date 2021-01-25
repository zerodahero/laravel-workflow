<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\TestObject;
use ZeroDaHero\LaravelWorkflow\Exceptions\DuplicateWorkflowException;
use ZeroDaHero\LaravelWorkflow\Exceptions\RegistryNotTrackedException;
use ZeroDaHero\LaravelWorkflow\WorkflowRegistry;

class WorkflowTrackingTest extends TestCase
{
    public function testIfPreventsDuplicates()
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

        $registryConfig = [
            'track_loaded' => true,
            'ignore_duplicates' => false,
        ];

        $registry = new WorkflowRegistry($config, $registryConfig);
        $subject = new TestObject;
        $workflow = $registry->get($subject);

        $this->expectException(DuplicateWorkflowException::class);
        $registry->addFromArray('straight', $config['straight']);
    }

    public function testIfAllowDuplicates()
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

        $registryConfig = [
            'track_loaded' => true,
            'ignore_duplicates' => true,
        ];

        $registry = new WorkflowRegistry($config, $registryConfig);
        $subject = new TestObject;
        $workflow = $registry->get($subject);

        $registry->addFromArray('straight', $config['straight']);

        $this->assertCount(1, $registry->getLoaded('Tests\Fixtures\TestObject'));
    }

    public function testIfGetLoadedWithoutTracking()
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

        $registryConfig = [
            'track_loaded' => false,
            'ignore_duplicates' => true,
        ];

        $registry = new WorkflowRegistry($config, $registryConfig);
        $subject = new TestObject;
        $workflow = $registry->get($subject);

        $this->expectException(RegistryNotTrackedException::class);
        $registry->getLoaded();
    }
}
