<?php

declare(strict_types=1);

namespace GraphQl\Generator;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use Memio\Memio\Config\Build;
use Memio\Model\Contract;
use Memio\Model\File;
use Memio\Model\Method;
use Memio\Model\Object as ModelObject;
use Memio\Model\Phpdoc\MethodPhpdoc;
use Memio\Model\Phpdoc\PropertyPhpdoc;
use Memio\Model\Phpdoc\ReturnTag;
use Memio\Model\Phpdoc\ThrowTag;
use Memio\Model\Phpdoc\VariableTag;
use Memio\Model\Property;

class InterfaceGenerator
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
    public function buildInterfaceTypes(string $namespace, string $to, DocumentNode $documentNode)
    {
        foreach ($documentNode->definitions as $definition) {
            if ($definition instanceof InterfaceTypeDefinitionNode) {
                $this->buildInterfaceType($namespace, $to, $definition);
            }
        }
    }

    protected function buildInterfaceType(string $namespace, string $to, InterfaceTypeDefinitionNode $interfaceNode): void
    {
        $className = $interfaceNode->name->value;
        $interfaceContract = Contract::make($namespace . '\\' . $className);

        $file = File::make($to . '/' . $className)
            ->setStructure($interfaceContract);

        foreach ($interfaceNode->fields as $field) {
            // Add the getter method.
            // @TODO Maybe a less naive camel case?
            $interfaceContract->addMethod(
                Method::make('get' . ucfirst($field->name->value))
                    ->setPhpdoc(MethodPhpdoc::make()
                        ->setReturnTag(ReturnTag::make(PhpHelper::getPhpDocType($field->type)))
                        ->addThrowTag(ThrowTag::make('\\' . PhpHelper::CLIENT_NAMESPACE . 'NotRequestedFieldException'))
                    )
            );
        }

        // Build the file and print it
        $prettyPrinter = Build::prettyPrinter();
        file_put_contents("$to/$className.php", $prettyPrinter->generateCode($file));
    }
}
