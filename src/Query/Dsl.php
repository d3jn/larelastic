<?php

namespace D3jn\Larelastic\Query;

use D3jn\Larelastic\Contracts\Models\Searchable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Traits\Macroable;

class Dsl extends Clause
{
    use Macroable {
        __call as macroCall;
    }

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
     */
    public function __construct(Builder $builder, ?Clause $parent = null)
    {
        $this->builder = $builder;

        parent::__construct($parent);
    }

    /**
     * Handle dynamic method calls.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return \D3jn\Larelastic\Query\Clause
     */
    public function __call(string $name, array $arguments)
    {
        if (static::hasMacro($name)) {
            return $this->macroCall($name, $arguments);
        }

        return parent::__call($name, $arguments);
    }

    /**
     * Handle dynamic property calls.
     *
     * @param string $name
     *
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
     * Get count based on formed query
     *
     * @return int
     */
    public function count(): int
    {
        return $this->builder->count();
    }

    /**
     * End working with current clause and jump back to parent level.
     *
     * @return \D3jn\Larelastic\Query\Clause|\D3jn\Larelastic\Query\Builder
     */
    public function end()
    {
        if ($this->parent === null) {
            return $this->builder;
        }

        return $this->parent;
    }

    /**
     * Get searchable instance by specified id or null if not found.
     *
     * @param string $id
     *
     * @return \D3jn\Larelastic\Contracts\Models\Searchable|null
     */
    public function find(string $id): ?Searchable
    {
        return $this->builder->find($id);
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
     * Get builder that wraps over this clause instance.
     *
     * @return \D3jn\Larelastic\Query\Builder
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
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
     * Get raw array response for formed query.
     *
     * @return array
     */
    public function raw(): array
    {
        return $this->builder->raw();
    }
}
