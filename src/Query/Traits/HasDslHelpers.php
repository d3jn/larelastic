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
        if (null === $this->dsl) {
            $this->dsl = new Dsl($this, null);
        }

        return $this->dsl;
    }

    /**
     * Inject DSL builder body parameters to request params array.
     *
     * @param array &$parameters
     */
    protected function injectDslParameters(array &$parameters)
    {
        if (null !== $this->dsl) {
            $parameters['body'] = array_merge($parameters['body'] ?? [], $this->dsl->toArray());
        }
    }
}
