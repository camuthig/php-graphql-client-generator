<?php

require_once '../vendor/autoload.php';
require_once 'schema.php';

var_dump(\GraphQL\Utils\SchemaPrinter::doPrint($schema));
