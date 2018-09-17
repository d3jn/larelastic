<?php

namespace D3jn\Larelastic\Query\Fulltext;

use D3jn\Larelastic\Query\Traits\HasMaxExpansions;
use D3jn\Larelastic\Query\Traits\HasAnalyzer;
use D3jn\Larelastic\Query\Traits\IsBoostable;
use D3jn\Larelastic\Query\FieldValueQuery;

class MatchPhrasePrefixQuery extends FieldValueQuery
{
    use IsBoostable, HasAnalyzer, HasMaxExpansions;

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
            'match_phrase_prefix' => [
                $this->field => [
                    'query' => $this->value
                ]
            ]
        ];

        $this->injectIntoArray('boost', $result['match'][$this->field]);
        $this->injectIntoArray('analyzer', $result['match'][$this->field]);

        return $result;
    }
}
