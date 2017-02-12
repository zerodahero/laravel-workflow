<?php

namespace LaravelWorkflow;

use WorkflowRegistry;

class ServiceProvider extends ServiceProvider
{
    protected $commands = [
        'LaravelWorkflow\Commands\WorkflowGraphvizDumpCommand',
    ];

    /**
    * Bootstrap the application services...
    *
    * @return void
    */
    public function boot()
    {
        $configPath = __DIR__ . '/../../config/config.php';

        $this->publishes([$configPath => config_path('workflow.php')], 'config');
    }

    /**
    * Register the application services.
    *
    * @return void
    */
    public function register()
    {
        $this->commands($this->commands);

        $this->app->singleton(
            'workflow', function ($app) {
                return new WorkflowRegistry($app['config']['workflow']);
            }
        );
    }

    /**
    * Get the services provided by the provider.
    *
    * @return array
    */
    public function provides()
    {
        return ['workflow'];
    }
}
