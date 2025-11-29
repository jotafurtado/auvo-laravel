<?php

namespace Jcf\Auvo\Query;

use Illuminate\Http\Client\PendingRequest;

class CustomerQuery extends QueryBuilder
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http, '/customers');
    }

    /**
     * Filter customers by segment ID.
     */
    public function segmentId(int $segmentId): static
    {
        $this->filters['segmentId'] = $segmentId;

        return $this;
    }

    /**
     * Filter customers by group ID.
     */
    public function groupId(int $groupId): static
    {
        $this->filters['groupId'] = $groupId;

        return $this;
    }

    /**
     * Filter only active customers.
     */
    public function active(): static
    {
        $this->filters['active'] = true;

        return $this;
    }

    /**
     * Filter customers by email.
     */
    public function email(string $email): static
    {
        $this->filters['email'] = $email;

        return $this;
    }

    /**
     * Filter customers by document (CNPJ/CPF).
     */
    public function document(string $document): static
    {
        $this->filters['document'] = $document;

        return $this;
    }

    /**
     * Filter customers by name.
     */
    public function name(string $name): static
    {
        $this->filters['name'] = $name;

        return $this;
    }
}
