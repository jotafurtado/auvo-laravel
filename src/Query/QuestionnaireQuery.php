<?php

namespace Jcf\Auvo\Query;

use Illuminate\Http\Client\PendingRequest;

class QuestionnaireQuery extends QueryBuilder
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http, '/questionnaires');
    }

    /**
     * Filter questionnaires by ID.
     */
    public function id(int $id): static
    {
        $this->filters['id'] = $id;

        return $this;
    }

    /**
     * Filter questionnaires linked to the given task IDs.
     *
     * @param  array<int, int>  $taskIds
     */
    public function taskIds(array $taskIds): static
    {
        $this->filters['taskIDs'] = $taskIds;

        return $this;
    }

    /**
     * Set the page number for pagination.
     */
    public function page(int $page): static
    {
        $this->param('page', $page);

        return $this;
    }

    /**
     * Set the number of records per page.
     */
    public function pageSize(int $size): static
    {
        $this->param('pageSize', $size);

        return $this;
    }

    /**
     * Set the sort order ("asc" or "desc").
     */
    public function order(string $order): static
    {
        $this->param('order', $order);

        return $this;
    }
}
