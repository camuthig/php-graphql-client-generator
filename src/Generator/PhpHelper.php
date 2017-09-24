<?php

declare(strict_types=1);

namespace GraphQl\Generator;

use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NonNullType;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ScalarType;

class PhpHelper
{
    const CLIENT_NAMESPACE = 'GraphQl\\Client\\';

    /**
     * @param NamedTypeNode $node
     *
     * @return string
     */
    public static function getNamedPhpType(NamedTypeNode $node)
    {
        switch ($node->name->value) {
            case ScalarType::INT:
                return 'int';
                break;
            case ScalarType::STRING:
                return 'string';
                break;
            case ScalarType::BOOLEAN:
                return 'bool';
                break;
            case ScalarType::ID:
                return 'string';
                break;
            case ScalarType::FLOAT:
                return 'float';
                break;
            default:
                // @TODO Enums end up here, so need to figure that out
                // Object returns will be this type
                return $node->name->value;
        }
    }

    public static function allowsNull(TypeNode $node):  bool
    {
        // @TODO This only looks at the top level and could probably use some more thought
        return !$node instanceof NonNullTypeNode;
    }

    public static function getPhpType(TypeNode $node): ?string
    {
        $nullable = '';
        if (self::allowsNull($node)) {
            $nullable = '?';
        }

        switch (true) {
            case $node instanceof NonNullTypeNode:
                return $nullable . self::getPhpType($node->type);
            case $node instanceof ListTypeNode:
                return $nullable . 'array';
            case $node instanceof IntType:
                return $nullable . 'int';
            case $node instanceof FloatType:
                return $nullable . 'float';
            case $node instanceof BooleanType:
                return $nullable . 'bool';
            case $node instanceof NamedTypeNode:
                return $nullable . self::getNamedPhpType($node);
            default:
                return $nullable . 'string';
        }
    }

    /**
     * @param TypeNode $node
     * @param bool $allowsNull
     *
     * @return string
     */
    public static function getPhpDocType(TypeNode $node, $allowsNull = false): string
    {
        $nullDefault = 'null|';
        switch (true) {
            case $node instanceof NonNullTypeNode:
                 $type = substr(self::getPhpDocType($node->type), strlen($nullDefault));
                 break;

            case $node instanceof ListTypeNode:
                $nestedType = self::getPhpDocType($node->type);

                $type = $nullDefault . implode('|', array_map(function ($type) { return $type . '[]'; }, explode('|', $nestedType)));
                break;

            case $node instanceof NamedTypeNode:
                $type = $nullDefault . self::getNamedPhpType($node);
                break;

            default:
                $type = $nullDefault . 'mixed';
        }

        return $type;
//        return self::allowsNull($node) ? 'null|' . $type : $type;
    }

    public static function isNonScalar(NamedTypeNode $node): bool
    {
        switch ($node->name->value) {
            case ScalarType::INT:
            case ScalarType::STRING:
            case ScalarType::BOOLEAN:
            case ScalarType::ID:
            case ScalarType::FLOAT:
                return false;
            default:
                // @TODO Enums end up here, so need to figure that out
                return true;
        }
    }

    public static function buildInputValidationChain(Node $type, $allowsNull = true)
    {
        $validation = null;
        switch (true) {
            case $type instanceof NonNullTypeNode:
                return self::buildInputValidationChain($type->type, false);
            case $type instanceof ListTypeNode:
                $validation = 'all()->' . self::buildInputValidationChain($type->type);
                break;
            case $type instanceof NamedTypeNode:
                switch ($type->name->value) {
                    case ScalarType::INT:
                        $validation = 'integer()';
                        break;
                    case ScalarType::STRING:
                        $validation = 'string()';
                        break;
                    case ScalarType::BOOLEAN:
                        $validation = 'boolean()';
                        break;
                    case ScalarType::ID:
                        $validation = 'string()';
                        break;
                    case ScalarType::FLOAT:
                        $validation = 'float()';
                        break;
                    default:
                        // @TODO Enums end up here, so need to figure that out
                        $klass = $type->name->value;
                        $validation = "isInstanceOf($klass::class)";
                }
                break;
        }

        if (!$validation) {
            return $validation;
        }

        if ($allowsNull) {
            return 'nullOr()->' . $validation;
        }

        return $validation;
    }

    public static function getInputValidation(string $name, Node $type): ?string
    {
        if ($chain = self::buildInputValidationChain($type)) {
            return "Assert\\that(\$$name)->" . $chain . ';';
        }

        return null;
    }
}
