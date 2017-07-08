<?php

declare(strict_types=1);

namespace Foo;

use GraphQl\Client\Option;

class Bar
{
    /**
     * @var string|Option
     */
    private $id;

    /**
     * @var int|Option
     */
    private $blah;

    /**
     * @param Option $id
     * @param Option $blah
     */
    public function __construct($id, $blah)
    {
        $this->id   = $id;
        $this->blah = $blah;
    }

    public static function fromArray(array $fields): self
    {
        $instance = new static(Option::none(), Option::none());

        if (!array_key_exists('id', $fields)) {
            $instance->id = Option::none();
        } else {
            $instance->id = Option::some($fields['id']);
        }

        if (!array_key_exists('blah', $fields)) {
            $instance->blah = Option::none();
        } else {
            $instance->blah = Option::some($fields['blah']);
        }

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
     * @return int
     */
    public function getBlah(): int
    {
        return $this->blah->get();
    }
}
