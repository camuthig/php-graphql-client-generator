<?php

namespace GraphQl\Generator;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Memio\Memio\Config\Build;
use Memio\Model\File;
use Memio\Model\Object as ModelObject;

class FieldSelectionGenerator
{
    /**
     * @param string       $namespace
     * @param string       $to
     * @param DocumentNode $documentNode
     */
    public function buildFieldSelections($namespace, $to, DocumentNode $documentNode)
    {
        foreach ($documentNode->definitions as $definition) {
            // Build a field selection for all object types except the special types Query and Mutation
            if ($definition instanceof ObjectTypeDefinitionNode
                && $definition->name->value !== 'Query'
                && $definition->name->value !== 'Mutation') {
                $this->buildFieldSelection($namespace, $to, $definition);
            }
        }
    }

    protected function buildFieldSelection($namespace, $to, ObjectTypeDefinitionNode $objectType)
    {
        $className      = $objectType->name->value . 'FieldSelection';
        $selectionClass = new ModelObject($namespace . '\\' . $className);

        $file = File::make($to . '/' . $className)
            ->setStructure($selectionClass);

        // @TODO Implement me

        $prettyPrinter = Build::prettyPrinter();
//        var_dump($prettyPrinter->generateCode($file));
//        file_put_contents("$to/$className.php", $prettyPrinter->generateCode($file));
    }
}
