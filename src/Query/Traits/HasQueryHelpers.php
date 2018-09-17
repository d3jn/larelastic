<?php

namespace D3jn\Larelastic\Query\Traits;

use D3jn\Larelastic\Query\Compound\BoolQuery;
use Closure;

trait HasQueryHelpers
{
    /**
     * Main query.
     *
     * @var \D3jn\Larelastic\Query\CompoundQuery
     */
    protected $query = null;

    /**
     * Return main bool object of this query.
     *
     * @param \Closure|null $closure
     *
     * @return mixed
     */
    public function bool(?Closure $closure = null)
    {
        if ($this->query === null) {
            $this->query = app()->make(BoolQuery::class);
        }

        if ($closure !== null) {
            $closure($this->query);

            return $this;
        }

        return $this->query;
    }
}
