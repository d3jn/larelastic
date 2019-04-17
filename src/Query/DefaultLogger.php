<?php

namespace D3jn\Larelastic\Query;

use D3jn\Larelastic\Contracts\Query\Logger;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class DefaultLogger implements Logger
{
    /**
     * Log query by specifying it's type, request body and result.
     *
     * @param string     $type
     * @param mixed      $request
     * @param mixed|null $result
     */
    public function logQuery(string $type, $request, $result = null)
    {
        if (! Config::get('larelastic.logging.enabled', false)) {
            return;
        }

        // TODO
        // Log::channel(Config::get('larelastic.logging.channel'));
    }
}
