<?php

namespace Foo;

use GraphQl\Client\Enum;

class Baz extends Enum
{
    const BAZ_ONE = 'BAZ_ONE';
    const BAZ_TWO = 'BAZ_TWO';
    const BAZ_THREE = 'BAZ_THREE';

    public static function BAZ_ONE()
    {
        return new static(self::BAZ_ONE);
    }

    public static function BAZ_TWO()
    {
        return new static(self::BAZ_TWO);
    }

    public static function BAZ_THREE()
    {
        return new static(self::BAZ_THREE);
    }
}
