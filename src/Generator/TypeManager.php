<?php

namespace GraphQl\Generator;

use GraphQL\Language\AST\DefinitionNode;
use GraphQL\Language\AST\EnumTypeDefinitionNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;

class TypeManager
{
    const SCALAR_TYPE    = 'SCALAR';
    const ENUM_TYPE      = 'ENUM';
    const UNION_TYPE     = 'UNION';
    const INPUT_TYPE     = 'INPUT_TYPE';
    const OUTPUT_TYPE    = 'OUTPUT_TYPE';
    const INTERFACE_TYPE = 'INTERFACE_TYPE';

    /**
     * @var array
     */
    private $customScalars = [];

    /**
     * @var array
     */
    private $enums = [];

    /**
     * @var array
     */
    private $unions = [];

    /**
     * @var array
     */
    private $inputTypes = [];

    /**
     * @var array
     */
    private $outputTypes = [];

    /**
     * @var array
     */
    private $interfaceTypes = [];

    /**
     * @param $name
     *
     * @return null|string
     */
    public function getTypeOf($name)
    {
        if (array_key_exists($name, $this->customScalars)) {
            return self::SCALAR_TYPE;
        } elseif (array_key_exists($name, $this->enums)) {
            return self::ENUM_TYPE;
        } elseif (array_key_exists($name, $this->unions)) {
            return self::UNION_TYPE;
        } elseif (array_key_exists($name, $this->inputTypes)) {
            return self::INPUT_TYPE;
        } elseif (array_key_exists($name, $this->outputTypes)) {
            return self::OUTPUT_TYPE;
        } elseif (array_key_exists($name, $this->interfaceTypes)) {
            return self::INTERFACE_TYPE;
        }

        return null;
    }

    public function getClassFor(string $name): string
    {
        if (array_key_exists($name, $this->customScalars)) {
            return $this->customScalars[$name];
        } elseif (array_key_exists($name, $this->enums)) {
            return $this->enums[$name];
        } elseif (array_key_exists($name, $this->unions)) {
            return $this->unions[$name];
        } elseif (array_key_exists($name, $this->inputTypes)) {
            return $this->inputTypes[$name];
        } elseif (array_key_exists($name, $this->outputTypes)) {
            return $this->outputTypes[$name];
        } elseif (array_key_exists($name, $this->interfaceTypes)) {
            return $this->interfaceTypes[$name];
        }

        return null;
    }

    /**
     * @param string $name  The name of the type
     * @param string $klass The FQCN of the class to register
     *
     * @return void
     */
    public function registerScalar(string $name, string $klass)
    {
        $this->customScalars[$name] = $klass;
    }

    /**
     * @param string $name  The name of the type
     * @param string $klass The FQCN of the class to register
     *
     * @return void
     */
    public function registerEnum(string $name, string $klass)
    {
        $this->enums[$name] = $klass;
    }

    /**
     * @param string $name  The name of the type
     * @param string $klass The FQCN of the class to register
     *
     * @return void
     */
    public function registerUnion(string $name, string $klass)
    {
        $this->unions[$name] = $klass;
    }

    /**
     * @param string $name  The name of the type
     * @param string $klass The FQCN of the class to register
     *
     * @return void
     */
    public function registerInputType(string $name, string $klass)
    {
        $this->inputTypes[$name] = $klass;
    }

    /**
     * @param string $name  The name of the type
     * @param string $klass The FQCN of the class to register
     *
     * @return void
     */
    public function registerOutputType(string $name, string $klass)
    {
        $this->outputTypes[$name] = $klass;
    }

    /**
     * @param string $name  The name of the type
     * @param string $klass The FQCN of the class to register
     *
     * @return void
     */
    public function registerInterface(string $name, string $klass)
    {
        $this->interfaceTypes[$name] = $klass;
    }

    /**
     * Register a generic node into the manager
     *
     * @param string         $namespace  The namespace to register the type under
     * @param DefinitionNode $definition The node to register
     */
    public function registerNode(string $namespace, DefinitionNode $definition)
    {
        switch (true) {
            case $definition instanceof ObjectTypeDefinitionNode:
                $this->registerOutputType($this->nodeName($definition), $this->nodeClass($namespace, $definition));
                break;
            case $definition instanceof InputObjectTypeDefinitionNode:
                $this->registerInputType($this->nodeName($definition), $this->nodeClass($namespace, $definition));
                break;
            case $definition instanceof ScalarTypeDefinitionNode:
                $this->registerScalar($this->nodeName($definition), $this->nodeClass($namespace, $definition));
                break;
            case $definition instanceof EnumTypeDefinitionNode:
                $this->registerEnum($this->nodeName($definition), $this->nodeClass($namespace, $definition));
                break;
            case $definition instanceof UnionTypeDefinitionNode:
                $this->registerUnion($this->nodeName($definition), $this->nodeClass($namespace, $definition));
                break;
            case $definition instanceof InterfaceTypeDefinitionNode:
                $this->registerInterface($this->nodeName($definition), $this->nodeClass($namespace, $definition));
                break;
        }
    }

    protected function nodeName(DefinitionNode $definitionNode): string
    {
        return $definitionNode->name->value;
    }

    protected function nodeClass(string $namespace, DefinitionNode $definitionNode): string
    {
        return $namespace . '\\' . $definitionNode->name->value;
    }
}
