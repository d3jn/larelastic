<?php

namespace D3jn\Larelastic\Query;

use BadMethodCallException;
use Illuminate\Support\Arr;
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
     * @param \D3jn\Larelastic\Query\Clause|null $parent
     */
    public function __construct(?Clause $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Handle dynamic method calls.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return \D3jn\Larelastic\Query\Clause
     */
    public function __call(string $name, array $arguments)
    {
        if (empty($arguments)) {
            if (!array_key_exists($name, $this->parameters)) {
                throw new BadMethodCallException("Trying to get value of unexisting clause field '%s'!");
            }

            return $this->parameters[$name];
        }

        // Second argument determines how value should be assigned to the specified key.
        $action = $arguments[1] ?? 'assign';
        switch ($action) {
            case 'assign':
                $this->parameters[$name] = $arguments[0];
                break;
            case 'append':
                if (array_key_exists($name, $this->parameters)) {
                    $this->parameters[$name] = Arr::wrap($this->parameters[$name]);
                    $this->parameters[$name][] = $arguments[0];
                } else {
                    $this->parameters[$name] = [$arguments[0]];
                }
                break;
            default:
                throw new LogicException("Unknown action '$action'!");
                break;
        }

        return $this;
    }

    /**
     * Handle dynamic property calls.
     *
     * @param string $name
     *
     * @return \D3jn\Larelastic\Query\Clause
     */
    public function __get(string $name)
    {
        if (!isset($this->parameters[$name])) {
            $this->parameters[$name] = new Clause($this);
        }

        return $this->parameters[$name];
    }

    /**
     * End working with current clause and jump back to parent level.
     *
     * @return \D3jn\Larelastic\Query\Clause
     */
    public function end()
    {
        if (null === $this->parent) {
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
