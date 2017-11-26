<?php

declare(strict_types=1);

namespace Camuthig\Graphql\Service\Manual\StarWars;

use Camuthig\Graphql\Service\Manual\StarWars\Contracts\StarWarsFinder as FinderContract;
use Camuthig\Graphql\Service\Manual\StarWars\Contracts\StarWarsMutator as MutatorContract;

class StarWarsService
{
    /**
     * @var MutatorContract
     */
    protected $mutator;

    /**
     * @var FinderContract
     */
    protected $finder;

    public function __construct()
    {
        $this->mutator = new StarWarsMutator();
        $this->finder  = new StarWarsFinder();
    }

    public function mutate(): MutatorContract
    {
        return $this->mutator;
    }

    public function query(): FinderContract
    {
        return $this->finder;
    }
}