<?php

namespace GraphQl\Client;

abstract class CustomScalar implements CustomScalarInterface
{
    /**
     * @var mixed
     */
    protected $value;

    public function __construct($value)
    {
        $this->value = $this->parse($value);
    }

    public function get()
    {
        return $this->value;
    }

    /**
     * The function called to convert the data into the correct format for over-the-wire transport
     *
     * @return mixed
     */
    abstract public function serialize();

    /**
     * The function called to convert the over-the-wire format value into the client's format
     * @return mixed
     */
    abstract public static function parse($value);
}
