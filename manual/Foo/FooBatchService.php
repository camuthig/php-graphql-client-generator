<?php

declare(strict_types=1);

namespace Foo;

class FooBatchService
{
    private $fields = [];
    /**
     * @param OnboardingFieldSelection $query
     *
     * @return BatchLabBackendService
     */
    public function getOnboarding(int $id, OnboardingFieldSelection $query): self
    {
        $this->fields[] = new Field('getOnboarding', ['id' => $id], $query);

        return $this;
    }

    /**
     * @return array
     */
    public function execute(): array
    {
        // @TODO Make a call to the GraphQL server with all of the stuff
        return [];
    }
}
