<?php

namespace D3jn\Larelastic\Models\Traits;

use D3jn\Larelastic\Models\Observers\SearchableObserver;
use Illuminate\Support\Facades\Config;

trait EloquentSearchable
{
    use Searchable, IntegratesWithEloquent;

    /**
     * Attach our observer to searchable entities using this trait.
     */
    public static function bootEloquentSearchable()
    {
        if (Config::get('larelastic.observe_searchable_models', true)) {
            static::observe(SearchableObserver::class);
        }
    }
}
