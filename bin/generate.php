<?php

require_once '../vendor/autoload.php';

$schemaPath = __DIR__ . '/simple_schema.graphqls';

$generatePath = __DIR__ . '/output/';

$typeManager = new \GraphQl\Generator\TypeManager();

$generator = new \GraphQl\Generator\ClientGenerator($typeManager);
$generator->generateFrom('Foo', $schemaPath, $generatePath);
