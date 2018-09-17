<?php

namespace D3jn\Larelastic\Query\Term;

use D3jn\Larelastic\Query\Traits\IsBoostable;
use D3jn\Larelastic\Query\FieldValueQuery;

class TermsQuery extends FieldValueQuery
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
            'terms' => [
                $this->field => $this->value
            ]
        ];

        $this->injectIntoArray('boost', $result['terms']);

        return $result;
    }
}
