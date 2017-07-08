<?php

namespace Foo;

use Assert;

class FooInput
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var null|string
     */
    private $stuff;

    /**
     * @var null[]|string[]
     */
    private $lst;

    /**
     * @param string          $id
     * @param null|string     $stuff
     * @param null[]|string[] $lst
     */
    public function __construct($id, $stuff, $lst)
    {
        Assert\that($id)->string();
        Assert\that($stuff)->nullOr()->string();
        Assert\that($lst)->all()->nullOr()->string();

        $this->$id = $id;
        $this->$stuff = $stuff;
        $this->$lst = $lst;
    }
}
