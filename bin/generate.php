<?php

require_once '../vendor/autoload.php';

$schemaPath = __DIR__ . '/simple_schema.graphqls';

$generatePath = __DIR__ . '/output/';

$generator = new \GraphQl\Generator\ClientGenerator();
$generator->generateFrom('Foo', $schemaPath, $generatePath);
