<?php

declare(strict_types=1);

namespace Camuthig\Graphql\ServiceGenerator\Generator;

use GraphQl\Client\Option;
use GraphQl\Client\OutputObject;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\TypeNode;
use Memio\Memio\Config\Build;
use Memio\Model\Argument;
use Memio\Model\Contract;
use Memio\Model\File;
use Memio\Model\FullyQualifiedName;
use Memio\Model\Method;
use Memio\Model\Object as ModelObject;
use Memio\Model\Phpdoc\MethodPhpdoc;
use Memio\Model\Phpdoc\ParameterTag;
use Memio\Model\Phpdoc\PropertyPhpdoc;
use Memio\Model\Phpdoc\ReturnTag;
use Memio\Model\Phpdoc\ThrowTag;
use Memio\Model\Phpdoc\VariableTag;
use Memio\Model\Property;

class OutputTypeGenerator
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
    public function buildOutputTypes($namespace, $to, DocumentNode $documentNode)
    {
        foreach ($documentNode->definitions as $definition) {
            if ($definition instanceof ObjectTypeDefinitionNode) {
                if (!in_array($definition->name->value, ['Query', 'Mutations', 'Subscriptions'])) {
                    $this->buildOutputType($namespace, $to, $definition);
                }
            }
        }
    }

    protected function buildOutputType($namespace, $to, ObjectTypeDefinitionNode $outputNode)
    {
        $className = $outputNode->name->value;
        $outputObject = new ModelObject($namespace . '\\' . $className);

        $file = File::make($to . '/' . $className)
            ->setStructure($outputObject);

        // Build constructor
        $this->addConstructor($outputObject, $outputNode);

        // Add the `fromArray` function
        $this->buildFromArray($outputObject, $outputNode);

        foreach ($outputNode->fields as $field) {
            // Add the private variable. Type is the type of the field plus `|Option`
            $outputObject->addProperty(Property::make($field->name->value)
                ->makePrivate()
                ->setPhpdoc(PropertyPhpdoc::make()
                    ->setVariableTag(new VariableTag(PhpHelper::getPhpDocType($field->type) . '|Option'))
                )
            );

            // Add the getter method.
            // @TODO Maybe a less naive camel case?
            $outputObject->addMethod(
                Method::make('get' . ucfirst($field->name->value))
                    ->setPhpdoc(MethodPhpdoc::make()
                        ->setReturnTag(new ReturnTag(PhpHelper::getPhpDocType($field->type)))
                        ->addThrowTag(new ThrowTag('\\' . PhpHelper::CLIENT_NAMESPACE . 'NotRequestedFieldException'))
                    )
                    ->setBody('        return $this->' . $field->name->value . '->get();')
            );
        }

        // Add interfaces
        foreach ($outputNode->interfaces as $interface) {
            $outputObject->implement(Contract::make($this->typeManager->getClassFor($interface->name->value)));
        }

        // Add dependencies
        $file
            ->addFullyQualifiedName(new FullyQualifiedName('Assert'))
            ->addFullyQualifiedName(new FullyQualifiedName(Option::class))
            ->addFullyQualifiedName(new FullyQualifiedName(OutputObject::class));

        $outputObject->extend(new ModelObject(OutputObject::class));

        // Build the file and print it
        $prettyPrinter = Build::prettyPrinter();
        file_put_contents("$to/$className.php", $prettyPrinter->generateCode($file));
    }

    protected function buildFromArray(ModelObject $outputObject, ObjectTypeDefinitionNode $objectNode)
    {

        $method = new Method('fromArray');

        $method->addArgument(new Argument('array', 'fields'));

        $noneInstanceArgs = implode(', ', array_map(function() { return 'Option::none()'; }, $objectNode->fields));

        $setters = [];

        // @TODO This is going to require actually having everything else ready in a type registry
        foreach ($objectNode->fields as $field) {
            $setters[] = $this->buildSetCall($field->type, $field->name->value);
        }

        $setters = implode("\n\n", $setters);

        $method->setBody(<<<BODY
        \$instance = new static($noneInstanceArgs);
        
        $setters
BODY
        );
        $outputObject->addMethod($method);
    }

    protected function buildSetCall(TypeNode $type, string $field, bool $isList = false, bool $isNull = false): string
    {
        if ($type instanceof ListTypeNode) {
            return $this->buildListSetCall($type, $field);
        }

        if ($type instanceof NonNullTypeNode) {
            // I need to dig deeper to get to the named type of the non-null
            if ($type->type instanceof ListTypeNode) {
                return $this->buildListSetCall($type->type, $field);
            } elseif ($type->type instanceof NamedTypeNode) {
                return $this->buildNamedSetCall($type->type, $field);
            }
        }

        if ($type instanceof NamedTypeNode) {
            return $this->buildNamedSetCall($type, $field);
        }

        throw new \Exception('Unable to build a setter for the given type node');
    }

    protected function buildNamedSetCall(NamedTypeNode $type, string $field): string
    {
        if (PhpHelper::isNonScalar($type)) {
            switch ($this->typeManager->getTypeOf($type->name->value)) {
                case TypeManager::ENUM_TYPE:
                    return sprintf(
                        '$instance->enumFromArray($fields, \'%s\', %s::class);',
                        $field,
                        $type->name->value
                    );
                case TypeManager::OUTPUT_TYPE:
                    return sprintf(
                        '$instance->instanceFromArray($fields, \'%s\', %s::class);',
                        $field,
                        $type->name->value
                    );
                case TypeManager::SCALAR_TYPE:
                    return sprintf(
                        '$instance->customScalarFromArray($fields, \'%s\', %s::class);',
                        $field,
                        $type->name->value
                    );
                case TypeManager::UNION_TYPE:
                    // @TODO
                default:
                    var_dump('Testing one two');
                    var_dump($type->name->value);
                    return '';
            }
        }

        return sprintf('$instance->scalarFromArray($fields, \'' . $field . '\');');
    }

    protected function buildListSetCall(ListTypeNode $type, string $field): string
    {
        $innerType = $type->type;
        if ($type->type instanceof NonNullTypeNode) {
            $innerType = $type->type->type;
        }

        if (PhpHelper::isNonScalar($innerType)) {
            // If it is a non-scalar, I need to map across the array handling creation
            $innerTypeName = $innerType->name->value;
            switch ($this->typeManager->getTypeOf($innerType->name->value)) {
                case TypeManager::ENUM_TYPE:
                    return <<<SET
\$instance->listFromArray(\$fields, '$field', function (\$val) { 
    return $innerTypeName::fromString(\$val);
});
SET;
                case TypeManager::OUTPUT_TYPE:
                    return <<<SET
\$instance->listFromArray(\$fields, '$field', function(\$val) {
    return $innerTypeName::fromArray(\$val);
});
SET;
                case TypeManager::SCALAR_TYPE:
                    return <<<SET
\$instance->listFromArray(\$fields, '$field', function(\$val) {
    return $innerTypeName::parse(\$val);
});
SET;

                case TypeManager::UNION_TYPE:
                default:
                    return '';
            }
        } else {
            // I have an array of scalars, so I can treat the setting as if it was a single scalar
            return sprintf('$instance->scalarFromArray($fields, \'%s\');', $field);
        }
    }

    /**
     * @param ModelObject              $inputObject
     * @param ObjectTypeDefinitionNode $outputNode
     */
    protected function addConstructor(ModelObject $inputObject, ObjectTypeDefinitionNode $outputNode)
    {
        $method      = new Method('__construct');
        $doc         = new MethodPhpdoc();
        $validations = [];
        $sets        = [];

        foreach ($outputNode->fields as $field) {
            // @TODO Handle default values. Will need to put those last as well. Enums will be tricky
            $method->addArgument(Argument::make(PhpHelper::getPhpType($field->type), $field->name->value));

            $doc->addParameterTag(ParameterTag::make(PhpHelper::getPhpDocType($field->type) . '|Option', $field->name->value));

            $validations[] = PhpHelper::getInputValidation($field->name->value, $field->type);
            $sets[]        = sprintf("\$this->\$%s = \$%s;", $field->name->value, $field->name->value);
        }

        $method->setPhpdoc($doc);

        $validationBody = "        " . implode("\n        ", $validations);
        $setBody        = "        " . implode("\n        ", $sets);
        $method->setBody($validationBody . "\n\n" . $setBody);

        $inputObject->addMethod($method);
    }
}
