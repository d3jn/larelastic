<?php

namespace D3jn\Larelastic\Query\Traits;

trait IsBoostable
{
    /**
     * Boost multiplier for this query.
     *
     * @var int|null
     */
    protected $boost = null;

    /**
     * Setter for boost.
     *
     * @param int $value
     *
     * @return $this
     */
    public function boost(int $value)
    {
        $this->boost = $value;

        return $this;
    }
}
