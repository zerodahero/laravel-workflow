<?php

namespace Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;

class TestWorkflowListener implements ShouldQueue
{
    /**
     * @return void
     */
    public function handle($event)
    {
        // NOTE: This doesn't need to do anything as we are just ensuring that the event
        //       can be serialized and dispatched to a queue.
        return;
    }
}
