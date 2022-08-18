<?php

namespace Tests;

use Mockery;
use ZeroDaHero\LaravelWorkflow\WorkflowRegistry;
use ZeroDaHero\LaravelWorkflow\Traits\WorkflowTrait;
use Symfony\Component\Workflow\Workflow as SymfonyWorkflow;

class WorkflowTraitTest extends BaseWorkflowTestCase
{
    use WorkflowTrait;

    /**
     * @test
     */
    public function testWorkflowApply()
    {
        $registryMock = Mockery::mock(WorkflowRegistry::class);
        $workflowMock = Mockery::mock(SymfonyWorkflow::class);

        $this->app->instance('workflow', $registryMock);

        $registryMock->shouldReceive('get')
            ->once()
            ->with($this, 'workflow17')
            ->andReturn($workflowMock);

        $workflowMock->shouldReceive('apply')
            ->once()
            ->with($this, 't1', ['banana' => 42]);

        $this->workflow_apply('t1', 'workflow17', ['banana' => 42]);
    }

    /**
     * @test
     */
    public function testWorkflowApplyDuckTyped()
    {
        $registryMock = Mockery::mock(WorkflowRegistry::class);
        $workflowMock = Mockery::mock(SymfonyWorkflow::class);

        $this->app->instance('workflow', $registryMock);

        $registryMock->shouldReceive('get')
            ->once()
            ->with($this, null)
            ->andReturn($workflowMock);

        $workflowMock->shouldReceive('apply')
            ->once()
            ->with($this, 't1', ['banana' => 42]);

        $this->workflow_apply('t1', ['banana' => 42]);
    }
}
