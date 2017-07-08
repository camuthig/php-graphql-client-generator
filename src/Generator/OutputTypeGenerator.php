<?php

declare(strict_types=1);

namespace GraphQl\Generator;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use Memio\Model\Method;
use Memio\Model\Object as ModelObject;
use Memio\Model\Phpdoc\MethodPhpdoc;
use Memio\Model\Phpdoc\ReturnTag;

class OutputTypeGenerator
{
    /**
     * @param string       $namespace
     * @param string       $to
     * @param DocumentNode $documentNode
     */
    public function buildOutputTypes($namespace, $to, DocumentNode $documentNode)
    {

    }

    /**
     * @param ModelObject                   $inputObject
     * @param InputObjectTypeDefinitionNode $inputNode
     */
    protected function addGetters(ModelObject $inputObject, InputObjectTypeDefinitionNode $inputNode)
    {
        foreach ($inputNode->fields as $field) {
            // @TODO This is a bit naive on the "camel case" front
            $getterName = 'get' . ucfirst($field->name->value);
            $returnType = PhpHelper::getPhpDocType($field->type);

            $inputObject
                ->addMethod(
                    Method::make($getterName)
                        ->setPhpdoc(
                            MethodPhpdoc::make()
                                ->setReturnTag(ReturnTag::make($returnType))
                        )
                );
        }
    }
}
