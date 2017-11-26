<?php

declare(strict_types=1);

namespace Camuthig\Graphql\ServiceGenerator\Client;

class Option
{
    /**
     * @var bool
     */
    private $isNone;

    /**
     * @var mixed|null
     */
    private $value;

    public static function none(): self
    {
        $instance = new static();

        $instance->isNone = true;
        $instance->value  = null;

        return $instance;
    }

    public static function some($value): self
    {
        $instance = new static();

        $instance->isNone = false;
        $instance->value  = $value;

        return $instance;
    }

    public function isNone(): bool
    {
        return $this->isNone;
    }

    /**
     * @return mixed|null
     *
     * @throws NotRequestedFieldException
     */
    public function get()
    {
        if ($this->isNone) {
            throw new NotRequestedFieldException();
        }
        return $this->value;
    }
}
