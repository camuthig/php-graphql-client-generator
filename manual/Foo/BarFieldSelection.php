<?php

declare(strict_types=1);

namespace Foo;

use GraphQl\Client\BaseFieldSelection;
use GraphQl\Client\Option;

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

    /**
     * @param Option|null|int $num
     *
     * @return BarFieldSelection
     */
    public function withBlah($num): self
    {
        $args = [];

        $num->isNone() ?: $args['num'] = $num;

        return $this->withSpecifiedField(self::BLAH, $args, null);
    }
}
