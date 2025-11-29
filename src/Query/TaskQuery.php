<?php

namespace Jcf\Auvo\Query;

use Illuminate\Http\Client\PendingRequest;

class TaskQuery extends QueryBuilder
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http, '/tasks');
    }

    /**
     * Filter tasks by period.
     */
    public function period(string $startDate, string $endDate): static
    {
        // Adiciona 'T00:00:00' se não tiver hora
        if (! str_contains($startDate, 'T')) {
            $startDate .= 'T00:00:00';
        }
        if (! str_contains($endDate, 'T')) {
            $endDate .= 'T23:59:59';
        }

        $this->filters['startDate'] = $startDate;
        $this->filters['endDate'] = $endDate;

        return $this;
    }

    /**
     * Filter tasks by user ID.
     */
    public function userId(int $userId): static
    {
        $this->filters['userId'] = $userId;

        return $this;
    }

    /**
     * Filter tasks by customer ID.
     */
    public function customerId(int $customerId): static
    {
        $this->filters['customerId'] = $customerId;

        return $this;
    }

    /**
     * Filter tasks by status.
     */
    public function status(string $status): static
    {
        $this->filters['status'] = $status;

        return $this;
    }

    /**
     * Filter tasks by team ID.
     */
    public function teamId(int $teamId): static
    {
        $this->filters['teamId'] = $teamId;

        return $this;
    }

    /**
     * Filter only scheduled tasks.
     */
    public function scheduled(): static
    {
        $this->filters['status'] = 'scheduled';

        return $this;
    }

    /**
     * Filter only completed tasks.
     */
    public function completed(): static
    {
        $this->filters['status'] = 'completed';

        return $this;
    }

    /**
     * Filter tasks by type ID.
     */
    public function type(int $taskTypeId): static
    {
        $this->filters['type'] = $taskTypeId;

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
     * Set the fields to be returned in the response.
     */
    public function selectFields(string $fields): static
    {
        $this->param('selectfields', $fields);

        return $this;
    }
}
