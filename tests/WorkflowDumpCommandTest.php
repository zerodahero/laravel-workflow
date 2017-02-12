<?php
use PHPUnit\Framework\TestCase;
use LaravelWorkflow\Commands\WorkflowDumpCommand;
use LaravelWorkflow\WorkflowRegistry;

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

class Config
{
    public static function get($name)
    {
        global $config;

        return $config;
    }
}

class Workflow
{
    public static function get($object, $name)
    {
        global $config;

        $workflowRegistry = new WorkflowRegistry($config);

        return $workflowRegistry->get($object, $name);
    }
}

class WorkflowRegistryTest extends TestCase
{
    public function testWorkflowCommand()
    {
        $command = Mockery::mock(WorkflowDumpCommand::class)
        ->makePartial()
        ->shouldReceive('argument')
        ->with('workflow')
        ->andReturn('straight')
        ->shouldReceive('option')
        ->with('format')
        ->andReturn('png')
        ->getMock();

        $command->handle();

        $this->assertTrue(file_exists('straight.png'));
    }
}
