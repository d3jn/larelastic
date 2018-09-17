<?php

namespace D3jn\Larelastic\Contracts;

use D3jn\Larelastic\Contracts\Models\Searchable;

interface Repository
{
    /**
     * Get records from repository.
     */
    public function all();

    /**
     * Paginate records and get specific page of records from repository.
     *
     * @param int $perPage
     * @param int $page
     */
    public function paginate($perPage, $page);

    /**
     * Get one record from repository.
     */
    public function find();

    /**
     * Apply scope.
     *
     * @param string $name
     */
    public function applyScope(Scope $scope);

    /**
     * Reset all applied scopes.
     */
    public function resetScopes();
}
