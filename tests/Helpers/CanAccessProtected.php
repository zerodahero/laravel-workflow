<?php

namespace Tests\Helpers;

use ReflectionClass;

trait CanAccessProtected
{
    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Get a protected/private property on a class.
     *
     * @param object &$object Instantiated object to get the property from.
     * @param string $property Property name to get.
     * @param mixed $propertyName
     *
     * @return mixed Property value
     */
    public function getProtectedProperty(&$object, $propertyName)
    {
        $property = (new ReflectionClass($object))->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
