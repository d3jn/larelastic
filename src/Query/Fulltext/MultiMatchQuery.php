<?php

namespace D3jn\Larelastic\Query\Fulltext;

use D3jn\Larelastic\Exceptions\UnsupportedTypeException;
use D3jn\Larelastic\Query\Traits\IsBoostable;
use D3jn\Larelastic\Query\FieldValueQuery;

class MultiMatchQuery extends FieldValueQuery
{
    use IsBoostable;

    /**
     * Multimatch type.
     *
     * @var string
     */
    protected $type = null;

    /**
     * Setter for type.
     *
     * @param string $type
     *
     * @return $this
     *
     * @throws \D3jn\Larelastic\Exceptions\UnsupportedTypeException
     */
    public function type(string $type = 'best_fields'): MultiMatchQuery
    {
        if (! in_array(
                $type,
                ['best_fields', 'most_fields', 'cross_fields', 'phrase', 'phrase_prefix']
            )
        ) {
            throw new UnsupportedTypeException("Type <$name> is not supported by bool query!");
        }

        $this->type = $type;
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
        $result = [
            'multi_match' => [
                'query' => $this->value,
                'fields' => $this->field
            ]
        ];

        $this->injectIntoArray('boost', $result['multi_match']);
        $this->injectIntoArray('type', $result['multi_match']);

        return $result;
    }
}
