<?php

namespace Tests\Fixtures;

class TestCustomObject
{
    public $state;

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }
}
