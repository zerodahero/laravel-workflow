<?php

namespace Tests;

use Orchestra\Testbench\TestCase;
use ZeroDaHero\LaravelWorkflow\WorkflowServiceProvider;
use ZeroDaHero\LaravelWorkflow\Facades\WorkflowFacade;

class BaseWorkflowTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [WorkflowServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Workflow' => WorkflowFacade::class,
        ];
    }
}
