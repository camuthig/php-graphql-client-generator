<?php

namespace GraphQl\Client;

use Assert;

abstract class Enum
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var
     */
    protected $options;

    /**
     * @param string $value
     */
    protected function __construct($value)
    {
        Assert\that($value)->inArray($this->options);

        $this->value = $value;
    }

    /**
     * @param $value
     *
     * @return static
     */
    public static function fromString($value)
    {
        return new static($value);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function get()
    {
        return $this->value;
    }
}
