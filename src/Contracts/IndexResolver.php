<?php

namespace D3jn\Larelastic\Contracts;

interface IndexResolver
{
    /**
     * Get index name for specified type.
     *
     * @param string      $type
     * @param string|null $index
     *
     * @return string
     */
    public function resolveIndexForType(string $type, ?string $index = null): string;
}
