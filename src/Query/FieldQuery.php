<?php

namespace D3jn\Larelastic\Query;

abstract class FieldQuery extends Query
{
    /**
     * Field this query is applied to.
     *
     * @var mixed
     */
    protected $field;

    /**
     * FieldQuery constructor.
     *
     * @param mixed $field 
     */
    public function __construct($field)
    {
        $this->field = $field;
    }
}
