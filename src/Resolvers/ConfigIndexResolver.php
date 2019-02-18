<?php

namespace D3jn\Larelastic\Resolvers;

use D3jn\Larelastic\Contracts\IndexResolver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class ConfigIndexResolver implements IndexResolver
{
    /**
     * Get index name for specified type.
     *
     * @param string      $type
     * @param string|null $index
     *
     * @return string
     */
    public function resolveIndexForType(string $type, ?string $index = null): string
    {
        $index = $index ?? Config::get("larelastic.type_indices.{$type}");

        if (empty($index)) {
            throw new NoIndexForTypeException("Failed to resolve index name for <$type>!");
        }

        return App::environment('testing')
            ? 'testing_' . $index
            : $index;
    }
}
