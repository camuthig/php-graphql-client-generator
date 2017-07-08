<?php

declare(strict_types=1);

namespace GraphQl\Client;

class Field
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array|null
     */
    private $arguments;

    /**
     * @var BaseFieldSelection|null
     */
    private $selection;

    public function __construct(string $name, ?array $arguments, ?BaseFieldSelection $selection)
    {
        $this->name      = $name;
        $this->arguments = $arguments;
        $this->selection = $selection;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array|null
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * @return BaseFieldSelection|null
     */
    public function getSelection(): ?BaseFieldSelection
    {
        return $this->selection;
    }
}
