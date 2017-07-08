<?php

namespace GraphQl\Client;

use Assert;

abstract class Enum
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value
     */
    protected function __construct($value)
    {
        Assert\that($value)->string();

        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    public function get()
    {
        return $this->value;
    }
}
