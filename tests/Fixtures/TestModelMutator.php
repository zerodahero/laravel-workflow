<?php

namespace Tests\Fixtures;

/**
 * NOTE: This is a very simplified version of what eloquent models do with the magic methods.
 */
class TestModelMutator extends TestModel
{
    public $context;

    public function setMarkingAttribute($value, $context)
    {
        $this->attributes['marking'] = $value;
        $this->context = $context;
    }
}
