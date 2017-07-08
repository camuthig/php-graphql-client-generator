<?php

declare(strict_types=1);

namespace GraphQl\Generator;

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
        }

        // Add service extension and use statements
        $serviceObject->extend(new ModelObject('GraphQl\\Client\\GraphQlService'));


        $file = File::make($to . '/' . $namespace)
            ->setStructure($serviceObject);

        // Add the dependencies
        $file
            ->addFullyQualifiedName(new FullyQualifiedName(PhpHelper::CLIENT_NAMESPACE . 'GraphQlService'))
            ->addFullyQualifiedName(new FullyQualifiedName(PhpHelper::CLIENT_NAMESPACE . 'Query'))
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
        $methodDoc->setReturnTag(ReturnTag::make($this->getReturnDoc($field->type)));

        // Set the doc block
        $method->setPhpdoc($methodDoc);

        // Add the body
        $method->setBody($this->buildBody($field));

        $serviceObject
            ->addMethod($method);
    }

    protected function getDecodeType(Type $type) {
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
        $resultType  = $this->getDecodeType($field->type);
        // @TODO Handle lists with `array_map`
        $returnValue = $resultType ? "$resultType::fromArray(\$result)" : '$result';

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
}
