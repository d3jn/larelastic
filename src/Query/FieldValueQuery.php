<?php

namespace D3jn\Larelastic\Query;

abstract class FieldValueQuery extends FieldQuery
{
    /**
     * Value for this term query.
     *
     * @var mixed
     */
    protected $value;

    /**
     * FieldValueQuery constructor.
     *
     * @param mixed $field 
     * @param mixed $value 
     */
    public function __construct($field, $value)
    {
        $this->value = $value;

        parent::__construct($field);
    }
}
