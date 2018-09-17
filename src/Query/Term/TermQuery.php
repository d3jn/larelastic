<?php

namespace D3jn\Larelastic\Query\Term;

use D3jn\Larelastic\Query\Traits\IsBoostable;
use D3jn\Larelastic\Query\FieldValueQuery;

class TermQuery extends FieldValueQuery
{
    use IsBoostable;

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
            'term' => [
                $this->field => [
                    'value' => $this->value
                ]
            ]
        ];

        $this->injectIntoArray('boost', $result['term'][$this->field]);

        return $result;
    }
}
