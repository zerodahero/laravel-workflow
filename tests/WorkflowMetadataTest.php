<?php

namespace Tests;

use Tests\Fixtures\TestObject;
use PHPUnit\Framework\TestCase;
use Illuminate\Events\Dispatcher;
use ZeroDaHero\LaravelWorkflow\WorkflowRegistry;

class WorkflowMetadataTest extends TestCase
{
    public function testIfMetadataRegisteredOnWorkflow()
    {
        $config = [
            'straight' => [
                'metadata' => [
                    'title' => 'test title',
                ],
                'supports' => ['Tests\Fixtures\TestObject'],
                'places' => [
                    'a',
                    'b' => [
                        'metadata' => [
                            'm1' => 'forks',
                        ],
                    ],
                    'c' => [
                        'metadata' => [
                            'm2' => 'spoons',
                        ],
                    ],
                ],
                'transitions' => [
                    't1' => [
                        'from' => 'a',
                        'to' => 'b',
                        'metadata' => [
                            'm3' => 'knives',
                        ],
                    ],
                    't2' => [
                        'from' => 'b',
                        'to' => 'c',
                    ],
                ],
            ],
        ];

        $registry = new WorkflowRegistry($config, null, new Dispatcher());
        $subject = new TestObject();
        $workflow = $registry->get($subject);

        $this->assertEquals(
            $config['straight']['metadata']['title'],
            $workflow->getMetadataStore()->getWorkflowMetadata()['title']
        );

        $this->assertEquals(
            $config['straight']['places']['b']['metadata']['m1'],
            $workflow->getMetadataStore()->getPlaceMetadata('b')['m1']
        );
        $this->assertEquals(
            $config['straight']['places']['c']['metadata']['m2'],
            $workflow->getMetadataStore()->getPlaceMetadata('c')['m2']
        );

        $this->assertEquals(
            $config['straight']['transitions']['t1']['metadata']['m3'],
            $workflow->getMetadataStore()->getTransitionMetadata(
                $workflow->getDefinition()->getTransitions()[0]
            )['m3']
        );
    }
}
