<?php

namespace Brexis\LaravelWorkflow;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

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
        $configPath = __DIR__ . '/../config/config.php';

        $this->publishes([$configPath => config_path('workflow.php')], 'config');

        $this->registerBladeExtensions();
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
     * Register blades extensions
     */
    private function registerBladeExtensions()
    {
        Blade::directive('workflow_can', function ($object, $transitionName, $workflowName = null) {
            $workflow = Workflow::get($object, $workflowName);

            return $workflow->can($object, $transitionName);
        });

        Blade::directive('workflow_transitions', function ($object) {
            $workflow = Workflow::get($object);

            return $workflow->getEnabledTransitions($object);
        });
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
