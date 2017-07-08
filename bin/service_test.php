<?php

require_once 'vendor/autoload.php';

use Foo\BarFieldSelection;
use Foo\FooFieldSelection;

$service = new \Foo\FooService('http://localhost:8080');

$barSelect = BarFieldSelection::bar()->withId()->withBlah(5);
$fooSelect = FooFieldSelection::foo()->withId()->withBars($barSelect);
$result = $service->foo(4, $fooSelect);


var_dump($result);

