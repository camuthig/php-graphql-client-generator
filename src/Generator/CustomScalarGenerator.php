<?php

namespace GraphQl\Generator;

use GraphQl\Client\DefaultCustomType;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use Memio\Memio\Config\Build;
use Memio\Model\File;
use Memio\Model\FullyQualifiedName;
use Memio\Model\Object as ModelObject;

class CustomScalarGenerator
{
    /**
     * @var TypeManager
     */
    private $typeManager;

    /**
     * @param TypeManager $typeManager
     */
    public function __construct(TypeManager $typeManager)
    {
        $this->typeManager = $typeManager;
    }

    /**
     * @param string       $namespace
     * @param string       $to
     * @param DocumentNode $documentNode
     */
    public function buildCustomScalars($namespace, $to, DocumentNode $documentNode)
    {
        foreach ($documentNode->definitions as $definition) {
            if ($definition instanceof ScalarTypeDefinitionNode) {
                $this->buildScalarType($namespace, $to, $definition);
            }
        }
    }

    /**
     * @param string                   $namespace
     * @param string                   $to
     * @param ScalarTypeDefinitionNode $scalarType
     */
    protected function buildScalarType($namespace, $to, ScalarTypeDefinitionNode $scalarType)
    {
        $className = $scalarType->name->value;
        $scalarClass = new ModelObject($namespace . '\\' . $className);

        // Extend the default scalar class
        $scalarClass->extend(new ModelObject(DefaultCustomType::class));

        $file = File::make($to . '/' . $className)
            ->setStructure($scalarClass);

        $file->addFullyQualifiedName(new FullyQualifiedName(DefaultCustomType::class));

        $prettyPrinter = Build::prettyPrinter();
        file_put_contents("$to/$className.php", $prettyPrinter->generateCode($file));
    }
}
