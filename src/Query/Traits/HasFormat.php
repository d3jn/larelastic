<?php

namespace D3jn\Larelastic\Query\Traits;

trait HasFormat
{
    /**
     * Format of this query.
     *
     * @var string|null
     */
    protected $format = null;

    /**
     * Setter for format.
     *
     * @param string $value
     *
     * @return $this
     */
    public function format(string $value)
    {
        $this->format = $value;

        return $this;
    }
}
