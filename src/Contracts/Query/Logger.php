<?php

namespace D3jn\Larelastic\Contracts\Query;

interface Logger
{
    /**
     * Log query by specifying it's type, request body and result.
     *
     * @param string     $type
     * @param mixed      $request
     * @param mixed|null $result
     */
    public function logQuery(string $type, $request, $result = null);
}
