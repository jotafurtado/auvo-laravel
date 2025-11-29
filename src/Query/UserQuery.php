<?php

namespace Jcf\Auvo\Query;

use Illuminate\Http\Client\PendingRequest;

class UserQuery extends QueryBuilder
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http, '/users');
    }

    /**
     * Filter users by type.
     *
     * @param  int  $userType  1 - user | 2 - team manager | 3 - administrator
     */
    public function userType(int $userType): static
    {
        $this->filters['userType'] = $userType;

        return $this;
    }

    /**
     * Filter users available for tasks.
     */
    public function availableForTasks(): static
    {
        $this->filters['unavailableForTasks'] = false;

        return $this;
    }

    /**
     * Filter users by email.
     */
    public function email(string $email): static
    {
        $this->filters['email'] = $email;

        return $this;
    }

    /**
     * Filter users by login.
     */
    public function login(string $login): static
    {
        $this->filters['login'] = $login;

        return $this;
    }
}
