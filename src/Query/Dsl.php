<?php

namespace D3jn\Larelastic\Query;

use D3jn\Larelastic\Contracts\Models\Searchable;
use Illuminate\Pagination\LengthAwarePaginator;

class Dsl extends Clause
{
    /**
     * Builder that wraps over this instance.
     *
     * @var \D3jn\Larelastic\Query\Builder
     */
    protected $builder;

    /**
     * Parameter constructor.
     *
     * @param \D3jn\Larelastic\Query\Builder $builder
     * @param \D3jn\Elastic\Dsl\Clause|null  $parent
     * @return mixed
     */
    public function __construct(Builder $builder, ?Clause $parent = null)
    {
        $this->builder = $builder;

        parent::__construct($parent);
    }

    /**
     * Handle dynamic property calls.
     *
     * @param string $name
     * @return \D3jn\Larelastic\Query\Dsl
     */
    public function __get(string $name)
    {
        if (! isset($this->parameters[$name])) {
            $this->parameters[$name] = new Dsl($this->builder, $this);
        }

        return $this->parameters[$name];
    }

    /**
     * Get searchable instance by specified id or null if not found.
     *
     * @param string $id
     * @return \D3jn\Larelastic\Contracts\Models\Searchable|null
     */
    public function find(string $id): ?Searchable
    {
        return $this->builder->find($id);
    }

    /**
     * Get raw array response for formed query.
     *
     * @return array
     */
    public function raw(): array
    {
        return $this->builder->raw();
    }

    /**
     * Get collection of Searchable instances based on formed query.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get(): \Illuminate\Support\Collection
    {
        return $this->builder->get();
    }

    /**
     * Return paginated collection of Searchable instances based on formed
     * query.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate(?int $perPage = null, string $pageName = 'page', ?int $currentPage = null): LengthAwarePaginator
    {
        return $this->builder->paginate($perPage, $pageName, $currentPage);
    }

    /**
     * Get count based on formed query
     *
     * @return int
     */
    public function count(): int
    {
        return $this->builder->count();
    }

    /**
     * Get builder that wraps over this clause instance.
     *
     * @return \D3jn\Larelastic\Query\Builder
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }
}
