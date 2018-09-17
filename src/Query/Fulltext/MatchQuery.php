<?php

namespace D3jn\Larelastic\Query\Fulltext;

use D3jn\Larelastic\Query\Traits\HasMinimumShouldMatch;
use D3jn\Larelastic\Query\Traits\HasOperator;
use D3jn\Larelastic\Query\Traits\IsBoostable;
use D3jn\Larelastic\Query\FieldValueQuery;

class MatchQuery extends FieldValueQuery
{
    use IsBoostable, HasOperator, HasMinimumShouldMatch;

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
            'match' => [
                $this->field => [
                    'query' => $this->value
                ]
            ]
        ];

        $this->injectIntoArray('boost', $result['match'][$this->field]);
        $this->injectIntoArray('operator', $result['match'][$this->field]);
        $this->injectIntoArray('minimumShouldMatch', $result['match'][$this->field]);

        return $result;
    }
}
