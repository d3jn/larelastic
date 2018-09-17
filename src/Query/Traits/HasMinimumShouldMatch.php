<?php

namespace D3jn\Larelastic\Query\Traits;

trait HasMinimumShouldMatch
{
    /**
     * Minimum required amount of should matches in filter context.
     *
     * @var int|null
     */
    protected $minimumShouldMatch = null;

    /**
     * Setter for minimum should match.
     *
     * @param int $value
     *
     * @return $this
     */
    public function minimumShouldMatch(int $value)
    {
        $this->minimumShouldMatch = $value;

        return $this;
    }
}
