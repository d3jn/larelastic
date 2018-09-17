<?php

namespace D3jn\Larelastic\Query;

use Illuminate\Support\Str;

abstract class Query
{
    /**
     * Return array representation of this query.
     *
     * Returns null if rule is not properly set/should not be used.
     *
     * @return array|null
     */
    abstract public function toArray(): ?array;

    /**
     * Inject key value into array.
     *
     * @param string $name
     * @param mixed $value
     * @param array &$params
     *
     * @return void
     */
    protected function injectIntoArray(string $name, array &$params): void
    {
        if ($this->{$name} !== null) {
            $key = Str::snake($name);

            $params[$key] = $this->{$name};
        }
    }
}
