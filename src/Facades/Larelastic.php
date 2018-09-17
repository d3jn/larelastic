<?php

namespace D3jn\Larelastic\Facades;

use Illuminate\Support\Facades\Facade;

class Larelastic extends Facade
{
    /**
     * Get facade accessor.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'larelastic';
    }
}
