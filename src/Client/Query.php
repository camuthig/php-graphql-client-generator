<?php

declare(strict_types=1);

namespace Camuthig\Graphql\ServiceGenerator\Client;

/**
 * @TODO This could instead be part of generated code that would part of the client and have custom functions
 */
class Query extends BaseFieldSelection
{
    public static function withAction(string $field, ?array $arguments, BaseFieldSelection $query): self
    {
        $instance = new static();

        $instance->withSpecifiedField($field, $arguments, $query);

        return $instance;
    }

    public function encode(bool $pretty = false): string
    {
        $encoded = parent::encode($pretty);

        return sprintf('query { %s }', $encoded);
    }
}
