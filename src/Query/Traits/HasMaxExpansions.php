<?php

namespace D3jn\Larelastic\Query\Traits;

trait HasMaxExpansions
{
    /**
     * Max expansions for this query.
     *
     * @var int|null
     */
    protected $maxExpansions = null;

    /**
     * Setter for max expansions.
     *
     * @param int $value
     *
     * @return $this
     */
    public function maxExpansions(int $value)
    {
        $this->maxExpansions = $value;

        return $this;
    }
}
