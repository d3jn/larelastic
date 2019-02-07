<?php

namespace D3jn\Larelastic\Contracts;

interface IndexResolver
{
    /**
     * Get index name for specified type.
     *
     * @param string $type
     * @return string
     */
    public function resolveIndexForType($type): string;
}
