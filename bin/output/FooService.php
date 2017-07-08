<?php

namespace Foo;

use GraphQl\Client\GraphQlService;
use GraphQl\Client\Query;
use Assert;

class FooService extends GraphQlService
{
    /**
     * @param string            $id
     * @param BarFieldSelection $fieldSelection
     *
     * @return Bar
     */
    public function bar($id, BarFieldSelection $fieldSelection)
    {
        $arguments = [];
 
        Assert\that($id)->string();
        
        $arguments['id'] = $id;
        
        $query = Query::withAction('bar', $arguments, $fieldSelection);

        $result = $this->send($query->encode());

        return Bar::fromArray($result);
    }

    /**
     * @param null|FooInput     $id
     * @param FooFieldSelection $fieldSelection
     *
     * @return Foo
     */
    public function foo($id, FooFieldSelection $fieldSelection)
    {
        $arguments = [];
 
        Assert\that($id)->nullOr()->isInstanceOf(FooInput::class);
        
        $arguments['id'] = $id;
        
        $query = Query::withAction('foo', $arguments, $fieldSelection);

        $result = $this->send($query->encode());

        return Foo::fromArray($result);
    }

    /**
     * @param null|string[]     $ids
     * @param null|string       $blah
     * @param FooFieldSelection $fieldSelection
     *
     * @return Foo[]
     */
    public function foos($ids, $blah, FooFieldSelection $fieldSelection)
    {
        $arguments = [];
 
        Assert\that($ids)->nullOr()->all()->string();
        Assert\that($blah)->nullOr()->string();
        
        $arguments['ids'] = $ids;
        $arguments['blah'] = $blah;
        
        $query = Query::withAction('foos', $arguments, $fieldSelection);

        $result = $this->send($query->encode());

        return Foo::fromArray($result);
    }
}
