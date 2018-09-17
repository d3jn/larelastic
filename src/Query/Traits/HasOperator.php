<?php

namespace D3jn\Larelastic\Query\Traits;

use D3jn\Larelastic\Exceptions\UnsupportedOperatorException;

trait HasOperator
{
    /**
     * Operator of this query.
     *
     * @var string|null
     */
    protected $operator = null;

    /**
     * Setter for operator.
     *
     * @param string $value
     *
     * @return $this
     *
     * @throws \D3jn\Larelastic\Exceptions\UnsupportedOperatorException
     */
    public function operator(string $value)
    {
        if (! in_array($value, ['and', 'or'])) {
            throw new UnsupportedOperatorException("Operator <$value> is not supported!");
        }

        $this->operator = $value;

        return $this;
    }
}
