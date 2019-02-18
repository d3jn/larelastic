<?php

namespace D3jn\Larelastic;

use D3jn\Larelastic\Contracts\Models\Searchable;
use D3jn\Larelastic\Exceptions\UnknownTypeException;
use D3jn\Larelastic\Exceptions\UnsupportedTypeException;
use D3jn\Larelastic\Query\Builder;
use D3jn\Larelastic\Query\Factory;
use Illuminate\Support\Facades\Config;

class Larelastic
{
    /**
     * Get query objects factory.
     *
     * @return \D3jn\Larelastic\Query\Factory
     */
    public function query(): Factory
    {
        return resolve('D3jn\Larelastic\Query\Factory');
    }

    /**
     * Try resolving unexistant method as new query object or type.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $types = Config::get('larelastic.types');
        $source = null;

        foreach ($types as $class) {
            if (! in_array(Searchable::class, class_implements($class))) {
                throw new UnsupportedTypeException(
                    "Class <{$class}> must implement Searchable contract! Check your types configuration in <config/larelastic.php>"
                );
            }

            $entity = new $class;
            if ($entity->getSearchType() == $name) {
                $source = $entity;
            }
        }

        if ($source == null) {
            throw new UnknownTypeException(
                "Can't generate Builder for type <$name> since such a type is not found within Larelastic types configuration!"
            );
        }

        return App::make(Builder::class, compact('source'));
    }
}
