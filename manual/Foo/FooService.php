<?php

declare(strict_types=1);

namespace Foo;

use GraphQl\Client\GraphQlService;
use GraphQl\Client\Query;

class FooService extends GraphQlService
{
    public function batch(): FooBatchService
    {
        return new FooBatchService();
    }

    public function foo(int $id, FooFieldSelection $fooFieldSelection): Foo
    {
        $arguments = [];

        $arguments['id'] = $id;

        $query = Query::withAction('foo', $arguments, $fooFieldSelection);

        $result = $this->send($query->encode());

        return Foo::fromArray($result);
    }

    /**
     * @param int[]|null        $ids
     * @param FooFieldSelection $fooFieldSelection
     *
     * @return Foo[]
     */
    public function foos($ids, FooFieldSelection $fooFieldSelection): array
    {
        $arguments = [];

        if ($ids !== null) {
            $arguments['ids'] = $ids;
        }

        $query = Query::withAction('foos', $arguments, $fooFieldSelection);

        $result = $this->send($query->encode());

        return array_map(function (array $element) {
            return Foo::fromArray($element);
        }, $result);
    }

    public function bar(int $id, BarFieldSelection $barFieldSelection): Bar
    {
        $arguments = [];

        $arguments['id'] = $id;

        $query = Query::withAction('bar', $arguments, $barFieldSelection);

        $result = $this->send($query->encode());

        return Bar::fromArray($result);
    }
}
