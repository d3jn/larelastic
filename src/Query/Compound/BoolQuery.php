<?php

namespace D3jn\Larelastic\Query\Compound;

use D3jn\Larelastic\Exceptions\UnknownArgumentTypeException;
use D3jn\Larelastic\Exceptions\UnsupportedTypeException;
use D3jn\Larelastic\Query\Traits\HasMinimumShouldMatch;
use D3jn\Larelastic\Query\Traits\IsBoostable;
use D3jn\Larelastic\Query\CompoundQuery;
use D3jn\Larelastic\Query\Query;

class BoolQuery extends CompoundQuery
{
    use IsBoostable, HasMinimumShouldMatch;

    /**
     * Array of types data.
     *
     * @var array
     */
    protected $types = [];

    /**
     * Try resolving unexistant method as type.
     *
     * If called without arguments then returns stored value for desired type
     * (query or array of queries). If called with arguments to put into type
     * it returns $this to allow for fluent chain calling.
     *
     * Note that if called without arguments when nothing was initialized
     * for that type it will initialize empty bool query by default and
     * return it.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     *
     * @throws \D3jn\Larelastic\Exceptions\UnsupportedTypeException
     * @throws \D3jn\Larelastic\Exceptions\UnknownArgumentTypeException
     */
    public function __call(string $name, array $arguments)
    {
        // If no arguments then we return stored value(s) for the type.
        if (empty($arguments)) {
            // Creating default bool query for this type if not setup before.
            if (! isset($this->types[$name])) {
                $this->types[$name] = app()->make(BoolQuery::class);
            }

            return $this->types[$name];
        }

        if (! in_array($name, ['must', 'should', 'filter', 'must_not'])) {
            throw new UnsupportedTypeException("Type <$name> is not supported by bool query!");
        }

        // If we pass one query then we transform it into array with one query element.
        if ($arguments[0] instanceof Query) {
            $arguments[0] = [$arguments[0]];
        }

        if (is_array($arguments[0])) {
            if (isset($this->types[$name]) && is_array($this->types[$name])) {
                $this->types[$name] = array_merge($this->types[$name], $arguments[0]);
            } else {
                // If existing value is not an array then it's a CompoundQuery
                // object and we overwrite it.
                $this->types[$name] = $arguments[0];
            }

            return $this;
        } else if ($arguments[0] instanceof CompoundQuery) {
            $this->types[$name] = $arguments[0];

            return $this;
        }

        throw new UnknownArgumentTypeException(
            "Query parameter for <$name> type must be either array of D3jn\Larelastic\Query\Query or instance of D3jn\Larelastic\Query\CompoundQuery"
        );
    }

    /**
     * Return array representation of this query.
     *
     * Returns null if rule is not properly set/should not be used.
     *
     * @return array|null
     */
    public function toArray(): ?array
    {
        $result = [];

        if (! empty($this->types)) {
            foreach ($this->types as $type => $value) {
                if (is_array($value)) {
                    foreach ($value as $query) {
                        $array = $query->toArray();

                        // Null-value indicates that query is not complete and
                        // should not be included.
                        if ($array !== null) {
                            $result[$type][] = $array; 
                        }
                    }
                } else {
                    $array = $value->toArray();

                    // Null-value indicates that query is not complete and
                    // should not be included.
                    if ($array !== null) {
                        $result[$type] = $value->toArray();
                    }
                }
            }
        }

        // If there nothing inside this bool query then it should indicate that
        // it's not complete and must be skipped by returning null-value.
        if (empty($result)) {
            return null;
        }

        $this->injectIntoArray('minimumShouldMatch', $result); 

        return ['bool' => $result];
    }
}
