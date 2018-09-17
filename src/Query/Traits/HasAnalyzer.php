<?php

namespace D3jn\Larelastic\Query\Traits;

trait HasAnalyzer
{
    /**
     * Analyzer to use with this query.
     *
     * @var string|null
     */
    protected $analyzer = null;

    /**
     * Setter for analyzer.
     *
     * @param string $value
     *
     * @return $this
     */
    public function analyzer(string $value)
    {
        $this->analyzer = $value;

        return $this;
    }
}
