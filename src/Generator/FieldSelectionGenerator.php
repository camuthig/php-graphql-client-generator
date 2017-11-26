<?php

namespace Camuthig\Graphql\ServiceGenerator\Generator;

use GraphQl\Client\BaseFieldSelection;
use GraphQl\Client\Option;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Memio\Memio\Config\Build;
use Memio\Model\Argument;
use Memio\Model\Constant;
use Memio\Model\File;
use Memio\Model\FullyQualifiedName;
use Memio\Model\Method;
use Memio\Model\Object as ModelObject;
use Memio\Model\Phpdoc\MethodPhpdoc;
use Memio\Model\Phpdoc\ParameterTag;
use Memio\Model\Phpdoc\ReturnTag;

class FieldSelectionGenerator
{
    /**
     * @var TypeManager
     */
    private $typeManager;

    public function __construct(TypeManager $typeManager)
    {
        $this->typeManager = $typeManager;
    }

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

        foreach ($objectType->fields as $field) {
            $this->addField($field, $selectionClass);
        }

        $file = File::make($to . '/' . $className)
            ->setStructure($selectionClass);

        // Add dependencies
        $file
            ->addFullyQualifiedName(FullyQualifiedName::make(Option::class))
            ->addFullyQualifiedName(FullyQualifiedName::make(BaseFieldSelection::class));

        // Add inheritance
        $selectionClass->extend(ModelObject::make(BaseFieldSelection::class));

        $prettyPrinter = Build::prettyPrinter();
        file_put_contents("$to/$className.php", $prettyPrinter->generateCode($file));
    }

    protected function addField(FieldDefinitionNode $fieldNode, ModelObject $selectionObject): void
    {
        $type = $fieldNode->type;

        while ($type instanceof ListTypeNode || $type instanceof NonNullTypeNode) {
            $type = $type->type;
        }

        // Make the setter
        // @TODO Maybe something less naive for naming
        $method          = Method::make('with' . ucfirst($fieldNode->name->value));
        $methodDoc       = MethodPhpdoc::make()->setReturnTag(ReturnTag::make('self'));
        $constantName    = strtoupper($fieldNode->name->value);
        $fieldSelection  = 'null';
        $args            = 'null';
        $argumentSetters = [];

        if ($this->typeManager->getTypeOf($type->name->value) === TypeManager::OUTPUT_TYPE) {
            $fieldSelection = '$fieldSelection';
            $className = ucfirst($type->name->value) . 'FieldSelection';

            $method->addArgument(Argument::make($className, 'fieldSelection'));
            $methodDoc->addParameterTag(ParameterTag::make($className, 'fieldSelection'));
        }

        // @TODO Parse the arguments
        foreach ($fieldNode->arguments as $argument) {
            $args = '$args';
            $argName = $argument->name->value;

            $argNodeType = $argument->type;
            while ($argNodeType instanceof ListTypeNode || $argNodeType instanceof NonNullTypeNode) {
                $argNodeType = $argNodeType->type;
            }

            // This is mixed because it is actually an option
            $method->addArgument(Argument::make('mixed', $argName));
            $methodDoc->addParameterTag(ParameterTag::make(Option::class . '|' . PhpHelper::getPhpDocType($argNodeType), $argName));

            // Log the setter we will need for this
            $argumentSetters[] = sprintf('        $%s->isNone() ?: $args[\'%s\'] = $%s;', $argName, $argName, $argName);
        }

        $body = <<<BODY
        return \$this->withSpecifiedField(self::$constantName, $args, $fieldSelection);
BODY;

        if ($argumentSetters) {
            $body = "\n" . $body;
            // Add argument setters as needed before the return
            foreach ($argumentSetters as $argumentSetter) {
                $body = $argumentSetter . "\n" . $body;
            }

            $body = '        $args = [];' . "\n\n" . $body;
        }

        // Set the body
        $method->setBody($body);

        // Set the docs
        $method->setPhpdoc($methodDoc);

        // Add a constant
        $selectionObject->addConstant(Constant::make($constantName, '\'' . $fieldNode->name->value . '\''));

        // Add the method
        $selectionObject->addMethod($method);
    }
}
