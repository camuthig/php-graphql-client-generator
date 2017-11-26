<?php

declare(strict_types=1);

namespace Camuthig\Graphql\Service\Manual\StarWars\Contracts;

interface StarWarsService
{
    public function mutator(): StarWarsMutator;

    public function finder(): StarWarsFinder;
}