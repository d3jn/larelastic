<?php

namespace D3jn\Larelastic\Query;

use D3jn\Larelastic\Exceptions\UnsupportedQueryException;
use D3jn\Larelastic\Query\Fulltext\MatchPhrasePrefixQuery;
use D3jn\Larelastic\Query\Fulltext\MatchPhraseQuery;
use D3jn\Larelastic\Query\Fulltext\MatchQuery;
use D3jn\Larelastic\Query\Fulltext\MultiMatchQuery;
use D3jn\Larelastic\Query\Query;
use D3jn\Larelastic\Query\Term\RangeQuery;
use D3jn\Larelastic\Query\Term\TermQuery;
use D3jn\Larelastic\Query\Term\TermsQuery;

class Factory
{
    /**
    * Array of supported queries. Key is helper function name, value is 
    * query class.
     *
     * @var array
     */
    protected $supportedQueries = [
        // Term based.
        'range' => RangeQuery::class,
        'term' => TermQuery::class,
        'terms' => TermsQuery::class,

        // Fulltext ones.
        'matchPhrasePrefix' => MatchPhrasePrefixQuery::class,
        'matchPhrase' => MatchPhraseQuery::class,
        'match' => MatchQuery::class,
        'multiMatch' => MultiMatchQuery::class,
    ];

    /**
     * Make query object based on supported queries map.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return \D3jn\Larelastic\Query\Query
     *
     * @throws \D3jn\Larelastic\Exceptions\UnsupportedQueryException;
     */
    public function __call(string $name, array $arguments)
    {
        if (! isset($this->supportedQueries[$name])) {
            throw new UnsupportedQueryException("Query <$name> is not supported!");
        }

        return new $this->supportedQueries[$name](...$arguments);
    }
}
