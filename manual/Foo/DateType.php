<?php

namespace Foo;

use GraphQl\Client\CustomScalarInterface;

/**
 * A simple implementation of
 *
 */
class DateType implements CustomScalarInterface
{
    /**
     * @var \DateTime
     */
    private $value;

    public function __construct(\DateTime $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return $this->value->format('c');
    }

    /**
     * @return string
     */
    public static function parse($value)
    {
        return new static(new \DateTime($value));
    }

    /**
     * @return \DateTime
     */
    public function get()
    {
        return $this->value;
    }
}
