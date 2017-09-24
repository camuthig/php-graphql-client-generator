<?php

namespace Foo;

use GraphQl\Client\Option;
use GraphQl\Client\OutputObject;

class Foo extends OutputObject
{
    /**
     * @var string|Option
     */
    private $id;

    /**
     * @var string|null|Option
     */
    private $stuff;

    /**
     * @var Bar[]|Option
     */
    private $bars;

    /**
     * @var Baz|Option
     */
    private $enm;

    /**
     * Foo constructor.
     *
     * @param Option $id
     * @param Option $stuff
     * @param Option $bars
     */
    public function __construct($id, $stuff, $bars, $enm)
    {
        $this->id    = $id;
        $this->stuff = $stuff;
        $this->bars  = $bars;
        $this->enm   = $enm;
    }

    public static function fromArray(array $fields): self
    {
        $instance = new static(Option::none(), Option::none(), Option::none(), Option::none());

        $instance->scalarFromArray($fields, 'id');

        $instance->scalarFromArray($fields, 'stuff');

        $instance->listFromArray($fields, 'bars', function ($elem) {
            return Bar::fromArray($elem);
        });

        $instance->customScalarFromArray($fields, 'dt', Date::class);

        $instance->enumFromArray($fields, 'enm', Baz::class);

        return $instance;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id->get();
    }

    /**
     * @return string|null
     */
    public function getStuff(): ?string
    {
        return $this->stuff->get();
    }

    /**
     * @return Bar[]
     */
    public function getBars(): array
    {
        return $this->bars->get();
    }
}
