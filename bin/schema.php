<?php

use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class Bar {
    public function getId() {
        return uniqid();
    }

    public function getBlah(int $num) {
        return $num + 1;
    }
}

class Foo {
    public function getId() {
        return uniqid();
    }

    public function getStuff() {
        return uniqid();
    }

    public function getBars()
    {
        return [new Bar(), new Bar()];
    }
}

$dateType = new \GraphQL\Type\Definition\CustomScalarType()

$barType = new ObjectType(
    [
        'name' => 'Bar',
        'fields' => [
            'id' => [
                'type' => Type::nonNull(Type::id()),
                'resolve' => function (Bar $root) {
                    return $root->getId();
                }
            ],
            'dateType' => [
                'type' => Type::
            ]
            'blah' => [
                'type' => Type::nonNull(Type::int()),
                'args' => [
                    'num' => [
                        'name' => 'num',
                        'type' => Type::nonNull(Type::int())
                    ]
                ],
                'resolve' => function (Bar $root, $args) {
                    return $root->getBlah($args['num']);
                }
            ]
        ]
    ]
);

$fooType = new ObjectType(
    [
        'name' => 'Foo',
        'fields' => [
            'id' => [
                'type' => Type::nonNull(Type::id()),
                'resolve' => function (Foo $root) {
                    return $root->getId();
                }
            ],
            'stuff' => [
                'type' => Type::string(),
                'resolve' => function (Foo $root) {
                    return $root->getStuff();
                }
            ],
            'bars' => [
                'type' => Type::listOf(Type::nonNull($barType)),
                'resolve' => function (Foo $root) {
                    return $root->getBars();
                }
            ]
        ]
    ]
);

$queryType      = new ObjectType(
    [
        'name'   => 'Query',
        'fields' => [
            'foo' => [
                'type'    => Type::nonNull($fooType),
                'args'    => [
                    'id' => ['type' => Type::nonNull(Type::id())],
                ],
                'resolve' => function () {
                    return new Foo();
                }
            ],
            'foos' => [
                'type'    => Type::nonNull(Type::listOf(Type::nonNull($fooType))),
                'args'    => [
                    'ids' => ['type' => Type::listOf(Type::nonNull(Type::id()))]
                ],
                'resolve' => function () {
                    return [new Foo(), new Foo()];
                }
            ],
            'bar' => [
                'type' => Type::nonNull($barType),
                'args' => [
                    'id' => ['type' => Type::nonNull(Type::id())]
                ],
                'resolve' => function () {
                    return new Bar();
                }
            ]
        ],
    ]
);

$schema         = new Schema(
    [
        'query'    => $queryType,
    ]
);

return $schema;
