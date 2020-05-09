<?php

namespace Tests;

use ZeroDaHero\LaravelWorkflow\Commands\WorkflowDumpCommand;
use Mockery;

class WorkflowDumpCommandTest extends BaseWorkflowTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']['workflow'] = [
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
    }

    public function testShouldThrowExceptionForUndefinedWorkflow()
    {
        $command = Mockery::mock(WorkflowDumpCommand::class)
            ->makePartial()
            ->shouldReceive('argument')
            ->with('workflow')
            ->andReturn('fake')
            ->shouldReceive('option')
            ->with('format')
            ->andReturn('png')
            ->shouldReceive('option')
            ->with('class')
            ->andReturn('Tests\Fixtures\TestObject')
            ->getMock();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Workflow fake is not configured.');
        $command->handle();
    }

    public function testShouldThrowExceptionForUndefinedClass()
    {
        $command = Mockery::mock(WorkflowDumpCommand::class)
            ->makePartial()
            ->shouldReceive('argument')
            ->with('workflow')
            ->andReturn('straight')
            ->shouldReceive('option')
            ->with('format')
            ->andReturn('png')
            ->shouldReceive('option')
            ->with('class')
            ->andReturn('Tests\Fixtures\FakeObject')
            ->getMock();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Workflow straight has no support for' .
            ' class Tests\Fixtures\FakeObject. Please specify a valid support' .
            ' class with the --class option.');
        $command->handle();
    }

    public function testWorkflowCommand()
    {
        if (file_exists('straight.png')) {
            unlink('straight.png');
        }

        $command = Mockery::mock(WorkflowDumpCommand::class)
            ->makePartial()
            ->shouldReceive('argument')
            ->with('workflow')
            ->andReturn('straight')
            ->shouldReceive('option')
            ->with('format')
            ->andReturn('png')
            ->shouldReceive('option')
            ->with('class')
            ->andReturn('Tests\Fixtures\TestObject')
            ->getMock();

        $command->handle();

        $this->assertTrue(file_exists('straight.png'));
    }
}
