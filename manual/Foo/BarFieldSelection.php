<?php

declare(strict_types=1);

namespace Foo;

use GraphQl\Client\BaseFieldSelection;

class BarFieldSelection extends BaseFieldSelection
{
    private const ID   = 'id';
    private const BLAH = 'blah';

    public static function bar(): self
    {
        return new static();
    }

    public function withId(): self
    {
        return $this->withSpecifiedField(self::ID, null, null);
    }

    public function withBlah(int $num): self
    {
        return $this->withSpecifiedField(self::BLAH, ['num' => $num], null);
    }
}
