<?php

namespace ZeroDaHero\LaravelWorkflow;

use Illuminate\Support\ServiceProvider;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class WorkflowServiceProvider extends ServiceProvider
{
    protected $commands = [
        'ZeroDaHero\LaravelWorkflow\Commands\WorkflowDumpCommand',
    ];

    /**
     * Bootstrap the application services...
     *
     * @return void
     */
    public function boot()
    {
        $configPath = $this->configPath();

        $this->publishes([
            "${configPath}/workflow.php" => $this->publishPath('workflow.php'),
            "${configPath}/workflow_registry.php" => $this->publishPath('workflow_registry.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            $this->configPath() . '/workflow_registry.php',
            'workflow_registry'
        );

        $this->commands($this->commands);

        $this->app->singleton('workflow', function ($app) {
            $workflowConfigs = $app->make('config')->get('workflow');
            $registryConfig = $app->make('config')->get('workflow_registry');

            return new WorkflowRegistry($workflowConfigs, $registryConfig);
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

    protected function configPath()
    {
        return __DIR__ . '/../config';
    }

    protected function publishPath($configFile)
    {
        if (function_exists('config_path')) {
            return config_path($configFile);
        } else {
            return base_path('config/' . $configFile);
        }
    }
}
