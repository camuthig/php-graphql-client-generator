<?php

namespace GraphQl\Generator;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\EnumTypeDefinitionNode;
use Memio\Memio\Config\Build;
use Memio\Model\Constant;
use Memio\Model\File;
use Memio\Model\Method;
use Memio\Model\Object as ModelObject;

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
            if ($definition instanceof EnumTypeDefinitionNode) {
                $this->buildEnumType($namespace, $to, $definition);
            }
        }
    }

    /**
     * @param string   $namespace
     * @param string   $to
     * @param EnumTypeDefinitionNode $enumType
     */
    protected function buildEnumType($namespace, $to, EnumTypeDefinitionNode $enumType)
    {
        $className = $enumType->name->value;
        $enumClass = new ModelObject($namespace . '\\' . $className);

        // Extend the base Enum class
        $enumClass->extend(new ModelObject(PhpHelper::CLIENT_NAMESPACE . 'Enum'));

        foreach ($enumType->values as $value) {
            // Add the constants to the class
            $enumValue = $value->name->value;
            $enumClass->addConstant(Constant::make($enumValue, $enumValue));

            // Add a function for the constant
            $method = Method::make($enumValue)->makeStatic();

            $method->setBody(<<<BODY
        return new static(self::\$$enumValue);
BODY
);
            $enumClass->addMethod($method);
        }

        $file = File::make($to . '/' . $className)
            ->setStructure($enumClass);

        $prettyPrinter = Build::prettyPrinter();
        file_put_contents("$to/$className.php", $prettyPrinter->generateCode($file));
    }
}
