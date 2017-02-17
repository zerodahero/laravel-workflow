<?php

namespace Brexis\LaravelWorkflow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class WorkflowFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'workflow';
    }
}
