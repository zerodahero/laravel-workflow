<?php

namespace Brexis\LaravelWorkflow;

use Illuminate\Support\ServiceProvider;
use WorkflowRegistry;

class WorkflowServiceProvider extends ServiceProvider
{
    protected $commands = [
        'Brexis\LaravelWorkflow\Commands\WorkflowDumpCommand',
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
