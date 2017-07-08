<?php

declare(strict_types=1);

namespace GraphQl\Client;

class NotRequestedFieldException extends \LogicException
{
    public function __construct()
    {
        parent::__construct(sprintf('Field was not requested'));
    }
}
