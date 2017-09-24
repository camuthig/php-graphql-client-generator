<?php

namespace GraphQl\Client;

/**
 * A simple implementation of custom type using strings. This class will be used for any scalar the
 * developer does not explicitly define a class for.
 */
class DefaultCustomType implements CustomScalarInterface
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public static function parse($value)
    {
        return new static(strval($value));
    }

    /**
     * @return string
     */
    public function get()
    {
        return $this->value;
    }
}
