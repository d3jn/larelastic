<?php

namespace D3jn\Larelastic\Query\Traits;

use D3jn\Larelastic\Query\Dsl;

trait HasDslHelpers
{
    /**
     * DSL query builder.
     *
     * @var \D3jn\Larelastic\Query\Dsl|null
     */
    protected $dsl = null;

    /**
     * Begin DSL query generation.
     *
     * @return \D3jn\Larelastic\Query\Dsl
     */
    public function query()
    {
        $this->dsl = new Dsl($this, null);

        return $this->dsl;
    }
}
