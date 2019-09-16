<?php

namespace D3jn\Larelastic\Query\Traits;

use D3jn\Larelastic\Query\Sql;

trait HasSqlHelpers
{
    /**
     * SQL query builder.
     *
     * @var \D3jn\Larelastic\Query\Sql|null
     */
    protected $sql = null;

    /**
     * Get SQL builder for request body.
     *
     * @return \D3jn\Larelastic\Query\Sql
     */
    public function sql(): Sql
    {
        if (null === $this->sql) {
            $this->sql = new Sql($this);
        }

        return $this->sql;
    }

    /**
     * Inject SQL builder body parameters to request params array.
     *
     * @param array &$parameters
     */
    protected function injectSqlParameters(array &$parameters)
    {
        if (null !== $this->sql) {
            // TODO.
            $parameters['body'] = [];
        }
    }
}
