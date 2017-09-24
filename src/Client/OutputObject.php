<?php

namespace GraphQl\Client;

abstract class OutputObject
{
    /**
     * @param array  $fields
     * @param string $key
     */
    protected function scalarFromArray(array $fields, $key)
    {
        if (!array_key_exists($key, $fields)) {
            $this->$key = Option::none();
        } else {
            $this->$key = Option::some($fields[$key]);
        }
    }

    /**
     * @param array  $fields
     * @param string $key
     * @param string $type The name of the class this represents. It should also have a `fromArray` method
     */
    protected function instanceFromArray(array $fields, $key, $type)
    {
        if (!array_key_exists($key, $fields)) {
            $this->$key = Option::none();
        } elseif (is_null($fields[$key])) {
            $this->$key = Option::some(null);
        } else {
            $parsed = call_user_func([$type, 'fromArray'], $fields[$key]);
            $this->$key = Option::some($parsed);
        }
    }

    /**
     * @param array    $fields
     * @param string   $key
     * @param \Closure $fromArray
     */
    protected function listFromArray(array $fields, $key, \Closure $fromArray)
    {
        if (!array_key_exists($key, $fields)) {
            $this->$key = Option::none();
        } elseif (is_null($fields[$key])) {
            $this->$key = Option::some(null);
        } else {
            $this->$key = Option::some(array_map($fromArray, $fields[$key]));
        }
    }

    /**
     * @param array  $fields
     * @param string $key
     * @param string $type The name of the enum class
     */
    protected function enumFromArray(array $fields, $key, $type)
    {
        if (!array_key_exists($key, $fields)) {
            $this->$key = Option::none();
        } elseif (is_null($fields[$key])) {
            $this->$key = Option::some(null);
        } else {
            $enum       = call_user_func([$type, 'fromString'], $fields[$key]);
            $this->$key = Option::some($enum);
        }
    }

    /**
     * Get a non-optional instance value
     *
     * @param $val
     * @param $type
     *
     * @return mixed|null
     */
    protected function instanceFromValue($val, $type)
    {
        if (is_null($val)) {
            return null;
        }

        return call_user_func([$type, 'fromArray'], $val);
    }

    /**
     * Get a non-optional enum value
     *
     * @param $val
     * @param $type
     *
     * @return mixed|null
     */
    protected function enumFromValue($val, $type)
    {
        if (is_null($val)) {
            return null;
        }

        return call_user_func([$type, 'fromString'], $val);
    }

    /**
     * @param array  $fields
     * @param string $key
     * @param string $type The name of the custom scalar class
     */
    protected function customScalarFromArray(array $fields, $key, $type)
    {
        if (!array_key_exists($key, $fields)) {
            $this->$key = Option::none();
        } elseif (is_null($fields[$key])) {
            $this->$key = Option::some(null);
        } else {
            $scalar     = call_user_func([$type, 'parse'], $fields[$key]);
            $this->$key = Option::some($scalar);
        }
    }
}
