<?php

declare(strict_types=1);

namespace GraphQl\Generator;

use GraphQl\Client\Enum;
use GraphQl\Client\GraphQlService;
use GraphQl\Client\Query;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\Type;
use GraphQL\Type\Definition\WrappingType;
use Memio\Memio\Config\Build;
use Memio\Model\Argument;
use Memio\Model\File;
use Memio\Model\FullyQualifiedName;
use Memio\Model\Method;
use Memio\Model\Object as ModelObject;
use Memio\Model\Phpdoc\MethodPhpdoc;
use Memio\Model\Phpdoc\ParameterTag;
use Memio\Model\Phpdoc\ReturnTag;

class ServiceGenerator
{
    /**
     * @var TypeManager
     */
    private $typeManager;

    public function __construct(TypeManager $typeManager)
    {
        $this->typeManager = $typeManager;
    }

    public function buildService(string $namespace, string $to, DocumentNode $documentNode): void
    {
        $serviceName   = explode('\\', $namespace);
        $serviceName   = array_pop($serviceName) . 'Service';
        $serviceObject = ModelObject::make($namespace . '\\' . $serviceName);

        foreach ($documentNode->definitions as $definition) {
            // Build functions for each root Query field
            if ($definition instanceof ObjectTypeDefinitionNode && $definition->name->value == 'Query') {
                foreach ($definition->fields as $field) {
                    $this->buildQueryMethods($serviceObject, $field);
                }
            }

            // @TODO Build mutation methods
        }

        // Add service extension and use statements
        $serviceObject->extend(new ModelObject(GraphQlService::class));


        $file = File::make($to . '/' . $namespace)
            ->setStructure($serviceObject);

        // Add the dependencies
        $file
            ->addFullyQualifiedName(new FullyQualifiedName(GraphQlService::class))
            ->addFullyQualifiedName(new FullyQualifiedName(Query::class))
            ->addFullyQualifiedName(new FullyQualifiedName('Assert'));

        $prettyPrinter = Build::prettyPrinter();
        file_put_contents("$to/$serviceName.php",$prettyPrinter->generateCode($file));
    }

    protected function getSelectionArgument(Node $node): ?string
    {
        if ($node instanceof NamedTypeNode && PhpHelper::isNonScalar($node)) {
            return ucfirst($node->name->value) . 'FieldSelection';
        }

        if ($node instanceof ListTypeNode) {
            return $this->getSelectionArgument($node->type);
        }

        if ($node instanceof NonNullTypeNode) {
            return $this->getSelectionArgument($node->type);
        }

        return null;
    }

    /**
     * @param Node $node
     *
     * @return string
     */
    protected function getReturnDoc(Node $node): string
    {
        $returnType = PhpHelper::getPhpDocType($node);

        return PhpHelper::allowsNull($node) ? $returnType . '|null' : $returnType;
    }

    protected function buildQueryMethods(ModelObject $serviceObject, FieldDefinitionNode $field)
    {
        $method = Method::make($field->name->value)
            ->makePublic();

        $methodDoc = MethodPhpdoc::make();

        // Handle arguments
        foreach ($field->arguments as $argument) {
            $type = PhpHelper::getPhpType($argument->type);
            $method
                ->addArgument(Argument::make($type, $argument->name->value));

            $methodDoc->addParameterTag(ParameterTag::make(PhpHelper::getPhpDocType($argument->type), $argument->name->value));
        }

        // Add the selection argument if it is an object type
        if ($selectionField = $this->getSelectionArgument($field->type)) {
            $method->addArgument(Argument::make($selectionField, 'fieldSelection'));

            $methodDoc->addParameterTag(ParameterTag::make($selectionField, 'fieldSelection'));
        }

        // Handle return type
        // @TODO Handle decoding results again
//        $methodDoc->setReturnTag(ReturnTag::make($this->getReturnDoc($field->type)));
        $methodDoc->setReturnTag(ReturnTag::make('array'));

        // Set the doc block
        $method->setPhpdoc($methodDoc);

        // Add the body
        $method->setBody($this->buildBody($field));

        $serviceObject
            ->addMethod($method);
    }

    protected function getDecodeType(Type $type): ?string
    {
        if ($type instanceof NamedTypeNode && $type->name->value !== 'ID') {
            return ucfirst($type->name->value);
        }

        // Both wrapping checks are necessary because of the odd class aliases
        if ($type instanceof ListTypeNode) {
            return $this->getDecodeType($type->type);
        }

        if ($type instanceof NonNullTypeNode) {
            return $this->getDecodeType($type->type);
        }

        if ($type instanceof WrappingType) {
            return $this->getDecodeType($type->getWrappedType());
        }

        return null;
    }

    protected function buildBody(FieldDefinitionNode $field): string
    {
        $action      = $field->name->value;
        // @TODO Handle decoding results again
//        $returnValue = $this->buildReturn($field);
        $returnValue = '$result';

        // Determine what validation is needed for input
        $argumentValidations = '';
        $arguments = '';
        /** @var InputValueDefinitionNode $argument */
        foreach ($field->arguments as $argument) {
            if ($validation = PhpHelper::getInputValidation($argument->name->value, $argument->type)) {
                $argumentValidations .= $validation . "\n        ";

                $arrayKey = $argument->name->value;
                $arrayValue = '$' . $argument->name->value;
                $arguments .= "\$arguments['$arrayKey'] = $arrayValue;\n        ";
            }
        }

        $body = <<<BODY
        \$arguments = [];
 
        $argumentValidations
        $arguments
        \$query = Query::withAction('$action', \$arguments, \$fieldSelection);

        \$result = \$this->send(\$query->encode());

        return $returnValue;
BODY;

        return $body;
    }

    protected function buildReturn(FieldDefinitionNode $definitionNode): string
    {
        $type = $definitionNode->type;

        if ($type instanceof ListTypeNode) {
            return $this->buildListTypeReturn($type);
        } elseif ($type instanceof NonNullTypeNode) {
            if ($type->type instanceof ListTypeNode) {
                return $this->buildListTypeReturn($type->type);
            } else {
                return $this->buildNamedTypeReturn($type->type);
            }
        } elseif ($type instanceof NamedTypeNode) {
            return $this->buildNamedTypeReturn($type);
        }

        return '\'\';';
    }

    protected function buildListTypeReturn(ListTypeNode $type): string
    {
        $innerType = $type->type;
        if ($type->type instanceof NonNullTypeNode) {
            $innerType = $type->type->type;
        }

        if (PhpHelper::isNonScalar($innerType)) {
            // If it is a non-scalar, I need to map across the array
            $innerTypeName = $innerType->name->value;
            switch ($this->typeManager->getTypeOf($innerType->name->value)) {
                case TypeManager::ENUM_TYPE:
                    return <<<SET
array_map(function (\$val) {
            return $innerTypeName::fromString(\$val);
        }, \$result)
SET;
                case TypeManager::OUTPUT_TYPE:
                    return <<<SET
array_map(function(\$val) {
            return $innerTypeName::fromArray(\$val);
        }, \$result)
SET;
                case TypeManager::SCALAR_TYPE:
                    return <<<SET
array_map(function(\$val) {
            return $innerTypeName::parse(\$val);
        }, \$result)
SET;

                case TypeManager::UNION_TYPE:
                    // @TODO
                default:
                    return '';
            }
        } else {
            // I have an array of scalars, so I can treat the setting as if it was a single scalar
            return '$result';
        }
    }

    protected function buildNamedTypeReturn(NamedTypeNode $type): string
    {
        if (PhpHelper::isNonScalar($type)) {
            switch ($this->typeManager->getTypeOf($type->name->value)) {
                case TypeManager::ENUM_TYPE:
                    return '\\' . $this->typeManager->getClassFor($type->name->value) . '::fromString($result)';
                case TypeManager::OUTPUT_TYPE:
                    return '\\' . $this->typeManager->getClassFor($type->name->value) . '::fromArray($result)';
                case TypeManager::SCALAR_TYPE:
                    return '\\' . $this->typeManager->getClassFor($type->name->value) . '::parse($result)';
                case TypeManager::UNION_TYPE:
                    // @TODO
                default:
                    throw new \Exception('No way to determine return structure for type named ' . $type->name->value);
            }
        }

        return sprintf('$result;');
    }
}
