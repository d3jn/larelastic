<?php

namespace D3jn\Larelastic\Query;

use BadMethodCallException;
use LogicException;

class Clause
{
    /**
     * Array of parameters.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Parent builder.
     *
     * @var \D3jn\Larelastic\Query\Clause|null
     */
    protected $parent;

    /**
     * Parameter constructor.
     *
     * @param  \D3jn\Larelastic\Query\Clause|null $parent
     * @return mixed
     */
    public function __construct(?Clause $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Handle dynamic property calls.
     *
     * @param  string $name
     * @return \D3jn\Larelastic\Query\Clause
     */
    public function __get(string $name): Clause
    {
        if (! isset($this->parameters[$name])) {
            $this->parameters[$name] = new Clause($this);
        }

        return $this->parameters[$name];
    }

    /**
     * Handle dynamic method calls.
     *
     * @param  string $name
     * @param  array  $arguments
     * @return \D3jn\Larelastic\Query\Clause
     */
    public function __call(string $name, array $arguments): Clause
    {
        if (empty($arguments)) {
            if (! array_key_exists($name, $this->parameters)) {
                throw new BadMethodCallException(sprintf(
                    'Trying to get value of unexisting clause field "%s"!',
                    $name
                ));
            }

            return $this->parameters[$name];
        }

        $this->parameters[$name] = count($arguments) === 1 ? $arguments[0] : $arguments;

        return $this;
    }

    /**
     * End working with current clause and jump back to parent level.
     *
     * @return \D3jn\Larelastic\Query\Clause
     */
    public function end(): Clause
    {
        if ($this->parent === null) {
            throw new LogicException('No upper level found! This end() call does nothing.');
        }

        return $this->parent;
    }

    /**
     * Get array representation of clause parameters.
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->parameters as $key => $parameter) {
            $value = $parameter instanceof Clause ? $parameter->toArray() : $parameter;

            // Empty children clauses must be represented as empty objects. This will guarantee
            // future conversion to a valid query JSON representation.
            $result[$key] = $value === [] ? (object) [] : $value;
        }

        return $result;
    }
}
