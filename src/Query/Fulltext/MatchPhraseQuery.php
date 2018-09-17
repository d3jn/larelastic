<?php

namespace D3jn\Larelastic\Query\Fulltext;

use D3jn\Larelastic\Query\Traits\HasAnalyzer;
use D3jn\Larelastic\Query\Traits\IsBoostable;
use D3jn\Larelastic\Query\FieldValueQuery;

class MatchPhraseQuery extends FieldValueQuery
{
    use IsBoostable, HasAnalyzer;

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
            'match_phrase' => [
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
