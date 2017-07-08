<?php

declare(strict_types=1);

namespace Foo;

use GraphQl\Client\Option;

class Foo
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
     * Foo constructor.
     *
     * @param Option $id
     * @param Option $stuff
     * @param Option $bars
     */
    public function __construct($id, $stuff, $bars)
    {
        $this->id    = $id;
        $this->stuff = $stuff;
        $this->bars  = $bars;
    }

    public static function fromArray(array $fields): self
    {
        $instance = new static(Option::none(), Option::none(), Option::none());

        if (!array_key_exists('id', $fields)) {
            $instance->id = Option::none();
        } else {
            $instance->id = Option::some($fields['id']);
        }

        if (!array_key_exists('stuff', $fields)) {
            $instance->stuff = Option::none();
        } else {
            $instance->stuff = Option::some($fields['stuff']);
        }

        // This is an option list of values. THis means we have to check for:
        // existence in the array, emptiness and null-ness to determine the correct
        // value to present the requester
        if (!array_key_exists('bars', $fields)) {
            $instance->bars = Option::none();
        } else {
            if ($fields['bars']) {
                $instance->bars = array_map(function (array $bar) {
                    return Bar::fromArray($bar);
                }, $fields['bars']);
            } elseif (is_array($fields['bars']) && empty($fields['bars'])) {
                $instance->bars = Option::some([]);
            } else {
                $instance->bars = Option::some(null);
            }
        }
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
