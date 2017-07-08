<?php

declare(strict_types=1);

namespace GraphQl\Client;

abstract class BaseFieldSelection
{
    /**
     * @var Field[]
     */
    private $fields = [];

    /**
     * @var string
     */
    protected $name;

    protected function withSpecifiedField(string $field, ?array $arguments, ?BaseFieldSelection $selection)
    {
        $this->fields[$field] = new Field($field, $arguments, $selection);

        return $this;
    }

    protected function encodeArgumentValue($value): string
    {
        switch (true) {
            case is_int($value):
            case is_float($value):
                return (string) $value;
            case is_array($value):
                return implode(',', array_map(function ($value) { return $this->encodeArgumentValue($value); }, $value));
            // @TODO Handle enums
            default:
                return sprintf('"%s"', $value);
        }
    }

    public function encode(bool $pretty = false): string
    {
        $fields = array_map(
            function (Field $field) {
                $encodedArguments = '';
                if ($field->getArguments()) {
                    // @TODO support arguments
                    $arguments = [];
                    if ($field->getArguments()) {
                        foreach ($field->getArguments() as $name => $value) {
                            $encodedValue = $this->encodeArgumentValue($value);
                            $arguments[] = sprintf('%s: %s', $name, $encodedValue);
                        }
                    }

                    $encodedArguments = $arguments ? sprintf('(%s)', implode(', ', $arguments), ''): '';
                }

                $encodedSelection = '';
                if ($field->getSelection()) {
                    $selection = $field->getSelection()->encode();

                    if ($selection) {
                        $encodedSelection = sprintf('{ %s }', $selection);
                    }
                }

                return sprintf('%s%s %s', $field->getName(), $encodedArguments, $encodedSelection);
            },
            $this->fields
        );

        return implode(', ', $fields);
    }
}
