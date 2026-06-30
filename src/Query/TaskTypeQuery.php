<?php

namespace Jcf\Auvo\Query;

use Illuminate\Http\Client\PendingRequest;

class TaskTypeQuery extends QueryBuilder
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http, '/tasktypes');
    }

    public function page(int $page): static
    {
        $this->param('page', $page);

        return $this;
    }

    public function pageSize(int $size): static
    {
        $this->param('pageSize', $size);

        return $this;
    }

    public function id(int $id): static
    {
        $this->filters['id'] = $id;

        return $this;
    }

    public function description(string $description): static
    {
        $this->filters['description'] = $description;

        return $this;
    }
}
