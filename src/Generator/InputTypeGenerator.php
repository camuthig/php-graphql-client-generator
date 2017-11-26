<?php

declare(strict_types=1);

namespace Camuthig\Graphql\ServiceGenerator\Generator;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use Memio\Memio\Config\Build;
use Memio\Model\Argument;
use Memio\Model\File;
use Memio\Model\FullyQualifiedName;
use Memio\Model\Method;
use Memio\Model\Object as ModelObject;
use Memio\Model\Phpdoc\MethodPhpdoc;
use Memio\Model\Phpdoc\ParameterTag;
use Memio\Model\Phpdoc\PropertyPhpdoc;
use Memio\Model\Phpdoc\VariableTag;
use Memio\Model\Property;

class InputTypeGenerator
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
     * @param string       $namespace    The namespace for the classes to be placed under
     * @param string       $to           Directory to write the files to
     * @param DocumentNode $documentNode
     */
    public function buildInputTypes($namespace, $to, DocumentNode $documentNode)
    {
        foreach ($documentNode->definitions as $definition) {
            if ($definition instanceof InputObjectTypeDefinitionNode) {
                $this->buildInputType($namespace, $to, $definition);
            }
        }
    }

    protected function buildInputType(string $namespace, string $to, InputObjectTypeDefinitionNode $inputNode)
    {
        $className   = $inputNode->name->value;
        $inputObject = ModelObject::make($namespace . '\\' . $className);

        $file = File::make($to . '/' . $className)
            ->setStructure($inputObject);

        // Add the private properties
        $this->addProperties($inputObject, $inputNode);

        // Add the constructor
        $this->addConstructor($inputObject, $inputNode);

        // Add dependencies
        $file->addFullyQualifiedName(new FullyQualifiedName('Assert'));

        $prettyPrinter = Build::prettyPrinter();
        file_put_contents("$to/$className.php", $prettyPrinter->generateCode($file));
    }

    /**
     * @param ModelObject                   $inputObject
     * @param InputObjectTypeDefinitionNode $inputNode
     */
    protected function addProperties(ModelObject $inputObject, InputObjectTypeDefinitionNode $inputNode)
    {
        foreach ($inputNode->fields as $field) {
            $propertyName = $field->name->value;
            $propertyType = PhpHelper::getPhpDocType($field->type);

            $inputObject->addProperty(
                Property::make($propertyName)->setPhpdoc(
                    PropertyPhpdoc::make()->setVariableTag(VariableTag::make($propertyType))
                )
            );
        }
    }

    /**
     * @param ModelObject                   $inputObject
     * @param InputObjectTypeDefinitionNode $inputNode
     */
    protected function addConstructor(ModelObject $inputObject, InputObjectTypeDefinitionNode $inputNode)
    {
        $method      = new Method('__construct');
        $doc         = new MethodPhpdoc();
        $validations = [];
        $sets        = [];

        foreach ($inputNode->fields as $field) {
            // @TODO Handle default values. Will need to put those last as well. Enums will be tricky
            $method->addArgument(Argument::make(PhpHelper::getPhpType($field->type), $field->name->value));

            $doc->addParameterTag(ParameterTag::make(PhpHelper::getPhpDocType($field->type), $field->name->value));

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
