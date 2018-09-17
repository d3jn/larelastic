<?php

namespace D3jn\Larelastic\Query\Traits;

use D3jn\Larelastic\Exceptions\UnknownTypeException;

trait HasZeroTermsQuery
{
    /**
     * Zero type query.
     *
     * @var string|null
     */
    protected $zeroTypeQuery = null;

    /**
     * Setter for zero type query.
     *
     * @param string $value
     *
     * @return $this
     *
     * @throws \D3jn\Larelastic\Exceptions\UnknownTypeException
     */
    public function zeroTypeQuery(string $value)
    {
        if (! in_array($value, ['none', 'all'])) {
            throw new UnknownTypeException("Zero type <$value> is not supported!");
        }

        $this->zeroTypeQuery = $value;

        return $this;
    }
}
