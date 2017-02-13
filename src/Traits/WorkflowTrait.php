<?php

namespace Brexis\LaravelWorkflow\Traits;

use Workflow;

trait WorkflowTrait
{
    public function workflow_can($object, $transition, $workflow = null)
    {
        return Workflow::get($object, $workflow)->can($object, $transition);
    }

    public function workflow_transitions($object)
    {
        return Workflow::get($object)->getEnabledTransitions($object);
    }
}
