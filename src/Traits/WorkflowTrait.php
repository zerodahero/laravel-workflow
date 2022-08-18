<?php

namespace ZeroDaHero\LaravelWorkflow\Traits;

use Workflow;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
trait WorkflowTrait
{
    public function workflow_apply($transition, $workflow = null, array $context = [])
    {
        if (is_array($workflow)) {
            $context = $workflow;
            $workflow = null;
        }

        return Workflow::get($this, $workflow)->apply($this, $transition, $context);
    }

    public function workflow_can($transition, $workflow = null)
    {
        return Workflow::get($this, $workflow)->can($this, $transition);
    }

    public function workflow_transitions($workflow = null)
    {
        return Workflow::get($this, $workflow)->getEnabledTransitions($this);
    }

    public function workflow_get($workflow = null)
    {
        return Workflow::get($this, $workflow);
    }
}
