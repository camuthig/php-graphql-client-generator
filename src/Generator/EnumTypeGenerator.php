<?php

namespace GraphQl\Generator;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Definition\EnumType;

class EnumTypeGenerator
{
    /**
     * @param string       $namespace
     * @param string       $to
     * @param DocumentNode $documentNode
     */
    public function buildEnumTypes($namespace, $to, DocumentNode $documentNode)
    {
        foreach ($documentNode->definitions as $definition) {
            if ($definition instanceof EnumType) {
                $this->buildEnumType($namespace, $to, $definition);
            }
        }
    }

    /**
     * @param string   $namespace
     * @param string   $to
     * @param EnumType $enumType
     */
    protected function buildEnumType($namespace, $to, EnumType $enumType)
    {

    }
}
