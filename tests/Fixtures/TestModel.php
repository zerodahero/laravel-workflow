<?php

namespace Tests\Fixtures;

/**
 * NOTE: This is a very simplified version of what eloquent models do with the magic methods.
 */
class TestModel
{
    public $attributes = [];

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $method = 'set' . ucfirst($name) . 'Attribute';

        if (method_exists($this, $method)) {
            $this->$method($value);

            return;
        }

        $this->attributes[$name] = $value;
    }
}
