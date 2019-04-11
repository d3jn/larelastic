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
     * Get DSL builder for request body.
     *
     * @return \D3jn\Larelastic\Query\Dsl
     */
    public function dsl(): Dsl
    {
        if ($this->dsl === null) {
            $this->dsl = new Dsl($this, null);
        }

        return $this->dsl;
    }
}
