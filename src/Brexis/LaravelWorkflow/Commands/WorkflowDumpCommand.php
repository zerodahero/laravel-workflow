<?php

namespace Brexis\LaravelWorkflow\Commands;

use Config;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Workflow\Dumper\GraphvizDumper;
use Symfony\Component\Workflow\Workflow as SynfonyWorkflow;
use Workflow;

class WorkflowDumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflow:dump
        {workflow : name of workflow from configuration}
        {--format=png}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GraphvizDumper dumps a workflow as a graphviz file.
        You can convert the generated dot file with the dot utility (http://www.graphviz.org/):';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $workflowName = $this->argument('workflow');
        $config = Config::get('workflow');

        if (!isset($config[$workflowName])) {
            throw new Exception("Workflow $workflowName is not configured.");
        }

        $className = $config[$workflowName]['supports'][0]; // todo: add option to select single class?

        $workflow = Workflow::get(new $className, $workflowName);

        $property = new \ReflectionProperty(SynfonyWorkflow::class, 'definition');
        $property->setAccessible(true);
        $definition = $property->getValue($workflow);

        $dumper = new GraphvizDumper();

        $format = $this->option('format');

        $dotCommand = "dot -T$format -o $workflowName.$format";

        $process = new Process($dotCommand);
        $process->setInput($dumper->dump($definition));
        $process->mustRun();
    }
}
