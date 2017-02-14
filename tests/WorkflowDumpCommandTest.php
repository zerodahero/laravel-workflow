<?php
use PHPUnit\Framework\TestCase;
use Brexis\LaravelWorkflow\Commands\WorkflowDumpCommand;
use Brexis\LaravelWorkflow\WorkflowRegistry;

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
        ->shouldReceive('option')
        ->with('class')
        ->andReturn('stdClass')
        ->getMock();

        $command->handle();

        $this->assertTrue(file_exists('straight.png'));
    }
}
