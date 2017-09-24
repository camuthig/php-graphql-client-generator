<?php

namespace GraphQl\Generator;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\EnumTypeDefinitionNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Language\Parser;

class ClientGenerator
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
     * @param string $filename The path to the GraphQL schema file
     * @param string $to       The directory to place generated files into
     */
    public function generateFrom(string $namespace, string $filename, string $to): void
    {
        // @TODO validate namespace and directory

        $schema = file_get_contents($filename);

        $parsedSchema = Parser::parse($schema);

        // Collect the types into the manager
        $this->collectTypes($namespace, $parsedSchema);

        // Build the enum types
        $enumTypeGenerator = new EnumTypeGenerator($this->typeManager);
        $enumTypeGenerator->buildEnumTypes($namespace, $to, $parsedSchema);

        // Build the custom scalars
        $customScalarGenerator = new CustomScalarGenerator($this->typeManager);
        $customScalarGenerator->buildCustomScalars($namespace, $to, $parsedSchema);

        // @TODO Build the union types

        // Build the input types
        $inputTypeGenerator = new InputTypeGenerator($this->typeManager);
        $inputTypeGenerator->buildInputTypes($namespace, $to, $parsedSchema);

        // Build the response objects
        $outputTypeGenerator = new OutputTypeGenerator($this->typeManager);
        $outputTypeGenerator->buildOutputTypes($namespace, $to, $parsedSchema);

        // Build the field selection types
        $fieldSelectionGenerator = new FieldSelectionGenerator();
        $fieldSelectionGenerator->buildFieldSelections($namespace, $to, $parsedSchema);

        // Build the service class itself
        $serviceGenerator = new ServiceGenerator();
        $serviceGenerator->buildService($namespace, $to, $parsedSchema);
    }

    /**
     * Iterate across the document and determine all of the class names and types available
     *
     * @param string       $namespace
     * @param DocumentNode $documentNode
     */
    private function collectTypes(string $namespace, DocumentNode $documentNode): void
    {
        foreach ($documentNode->definitions as $definition) {
            $this->typeManager->registerNode($namespace, $definition);
        }
    }
}
