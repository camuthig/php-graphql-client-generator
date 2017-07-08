<?php

declare(strict_types=1);

namespace Foo;

use GraphQl\Client\BaseFieldSelection;

class FooFieldSelection extends BaseFieldSelection
{
    private const ID    = 'id';
    private const STUFF = 'stuff';
    private const BARS  = 'bars';

    public static function foo(): self
    {
        return new static();
    }

    public function withId(): self
    {
        return $this->withSpecifiedField(self::ID, null, null);
    }

    public function withStuff(): self
    {
        return $this->withSpecifiedField(self::STUFF, null, null);
    }

    public function withBars(BarFieldSelection $fieldSelection): self
    {
        return $this->withSpecifiedField(self::BARS, null, $fieldSelection);
    }
}
