<?php

declare(strict_types=1);

namespace GraphQl\Generator;

use GraphQL\Language\Parser;

class ClientGenerator
{
    /**
     * @param string $filename The path to the GraphQL schema file
     * @param string $to       The directory to place generated files into
     */
    public function generateFrom(string $namespace, string $filename, string $to): void
    {
        // @TODO validate namespace and directory

        $schema = file_get_contents($filename);

        $parsedSchema = Parser::parse($schema);

        // Build the enum types
        $enumTypeGenerator = new EnumTypeGenerator();
        $enumTypeGenerator->buildEnumTypes($namespace, $to, $parsedSchema);

        // Build the input types
        $inputTypeGenerator = new InputTypeGenerator();
        $inputTypeGenerator->buildInputTypes($namespace, $to, $parsedSchema);

        // Build the field selection types

        // Build the response objects
        $outputTypeGenerator = new OutputTypeGenerator();
        $outputTypeGenerator->buildOutputTypes($namespace, $to, $parsedSchema);

        // Build the service class itself
        $serviceGenerator = new ServiceGenerator();
        $serviceGenerator->buildService($namespace, $to, $parsedSchema);
    }
}
