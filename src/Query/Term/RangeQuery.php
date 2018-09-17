<?php

namespace D3jn\Larelastic\Query\Term;

use D3jn\Larelastic\Query\Traits\IsBoostable;
use D3jn\Larelastic\Query\Traits\HasFormat;
use D3jn\Larelastic\Query\FieldQuery;

class RangeQuery extends FieldQuery
{
    use IsBoostable, HasFormat;

    /**
     * Gt or gte.
     *
     * @var mixed
     */
    protected $bottomRule;

    /**
     * Margin value for bottom range limit.
     *
     * @var mixed
     */
    protected $bottomValue;

    /**
     * Lt or lte.
     *
     * @var mixed
     */
    protected $topRule;

    /**
     * Margin value for top range limit.
     *
     * @var mixed
     */
    protected $topValue;

    /**
     * Timezone for values.
     *
     * @var mixed
     */
    protected $timeZone = null;

    /**
     * QueryWithField constructor.
     *
     * @param string $field 
     */
    public function __construct(string $field)
    {
        parent::__construct($field);
    }

    /**
     * Setter for lt.
     *
     * @return $this
     */
    public function lt($value): RangeQuery
    {
        $this->topRule = 'lt';
        $this->topValue = $value;

        return $this;
    }

    /**
     * Setter for lte.
     *
     * @return $this
     */
    public function lte($value): RangeQuery
    {
        $this->topRule = 'lte';
        $this->topValue = $value;

        return $this;
    }
    
    /**
     * Setter for gt.
     *
     * @return $this
     */
    public function gt($value): RangeQuery
    {
        $this->bottomRule = 'gt';
        $this->bottomValue = $value;

        return $this;
    }

    /**
     * Setter for gte.
     *
     * @return $this
     */
    public function gte($value): RangeQuery
    {
        $this->bottomRule = 'gte';
        $this->bottomValue = $value;

        return $this;
    }

    /**
     * Setter for timeZone.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function timeZone($value): RangeQuery
    {
        $this->timeZone = $value;

        return $this;
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
        if ($this->bottomRule === null && $this->topRule === null) {
            return null;
        }

        $params = [];
        if ($this->bottomRule !== null) {
            $params[$this->bottomRule] = $this->bottomValue;
        }
        if ($this->topRule !== null) {
            $params[$this->topRule] = $this->topValue;
        }

        $this->injectIntoArray('boost', $params);
        $this->injectIntoArray('format', $params);
        $this->injectIntoArray('timeZone', $params);

        return [
            'range' => [
                $this->field => $params
            ]
        ];
    }
}
