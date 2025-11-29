<?php

namespace Jcf\Auvo\Query;

use Illuminate\Http\Client\PendingRequest;

class TeamQuery extends QueryBuilder
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http, '/teams');
    }

    /**
     * Filter only active teams.
     */
    public function active(): static
    {
        $this->filters['active'] = true;

        return $this;
    }

    /**
     * Filter teams by manager ID.
     */
    public function managerId(int $managerId): static
    {
        $this->filters['managerId'] = $managerId;

        return $this;
    }

    /**
     * Filter teams by name.
     */
    public function name(string $name): static
    {
        $this->filters['name'] = $name;

        return $this;
    }
}
