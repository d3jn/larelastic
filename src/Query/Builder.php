<?php

namespace D3jn\Larelastic\Query;

use D3jn\Larelastic\Query\Traits\HasQueryHelpers;
use D3jn\Larelastic\Contracts\Models\Searchable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Elasticsearch\Client;
use Closure;

class Builder
{
    use HasQueryHelpers;

    /**
     * Searchable source for type.
     *
     * @var \D3jn\Larelastic\Contracts\Models\Searchable
     */
    protected $source;

    /**
     * ElasticSearch native PHP client.
     *
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * Query of the search request.
     *
     * @var array
     */
    protected $queryRaw;

    /**
     * Offset for results.
     *
     * @var int|null
     */
    protected $offset = null;

    /**
     * Limit for results.
     *
     * @var int|null
     */
    protected $limit = null;

    /**
     * Order by fields.
     *
     * @var array
     */
    protected $orderBy = [];

    /**
     * Highlight object for constructing highlight parameters.
     *
     * @var \D3jn\Larelastic\Query\Highlight|null
     */
    protected $highlight = null;

    /**
     * Raw highlight array parameters.
     *
     * @var array|null
     */
    protected $highlightRaw = null;

    /**
     * Raw request for entire query.
     *
     * @var array|null
     */
    protected $requestRaw = null;

    /**
     * The relationships that should be eager loaded.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * Builder constructor.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $source
     * @param \Elasticsearch\Client $client
     */
    public function __construct(Searchable $source, Client $client)
    {
        $this->source = $source;
        $this->client = $client;

        // Initializing.
        $this->queryRaw = ['match_all' => (object) []];
    }

    /**
     * Set request raw parameters for entire query.
     *
     * Note that some of those fields will be overriden if also featured in
     * query object via other (more specific) methods.
     *
     * @param array $params
     *
     * @return $this
     */
    public function requestRaw(array $params): Builder
    {
        $this->requestRaw = $params;

        return $this;
    }

    /**
     * Set query parameters for request by raw array of parameters.
     *
     * @param array $query
     *
     * @return $this
     */
    public function queryRaw(array $query): Builder
    {
        $this->queryRaw = $query;

        return $this;
    }

    /**
     * Limit results count.
     *
     * @param int limit
     *
     * @return $this
     */
    public function limit(int $limit): Builder
    {
        // null value means this parameter won't be mentioned in request.
        if ($limit <= 0) {
            $limit = null;
        }

        $this->limit = $limit;

        return $this;
    }

    /**
     * Getter for limit.
     *
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Offset results.
     *
     * @param int offset
     *
     * @return $this
     */
    public function offset(int $offset): Builder
    {
        // null value means this parameter won't be mentioned in request.
        if ($offset <= 0) {
            $offset = null;
        }

        $this->offset = $offset;

        return $this;
    }

    /**
     * Getter for offset.
     *
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * Specify order for query.
     *
     * @param string $field
     * @param string $rule
     *
     * @return $this
     */
    public function orderBy(string $field, string $rule = 'desc'): Builder
    {
        $this->orderBy[] = [$field => $rule];

        return $this;
    }

    /**
     * Getter for order by.
     *
     * @return array
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * Highlight specified fields in the request by raw array of parameters.
     *
     * @param array $params
     *
     * @return $this
     */
    public function highlightRaw(array $params): Builder
    {
        $this->highlight = array_merge($this->highlight, $fields);

        return $this;
    }

    /**
     * Getter for highlight.
     *
     * @param \Closure|null $closure
     *
     * @return $this|\D3jn\Larelastic\Query\Highlight
     */
    public function highlight(?Closure $closure = null)
    {
        if ($this->highlight === null) {
            $this->highlight = app()->make(Highlight::class);
        }

        if ($closure !== null) {
            $closure($this->highlight);

            return $this;
        }

        return $this->highlight;
    }

    /**
     * Set the relationships that should be eager loaded.
     *
     * @param string ...$relations
     *
     * @return $this
     */
    public function with(string ...$relations)
    {
        $this->relations = array_merge($this->relations, $relations);

        return $this;
    }

    /**
     * Prevent the specified relations from being eager loaded.
     *
     * @param string ...$relations
     *
     * @return $this
     */
    public function without(string ...$relations)
    {
        $this->relations = array_diff_key($this->relations, $relations);

        return $this;
    }

    /**
     * Getter for relations.
     *
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * Get searchable instance by specified id or null if not found.
     *
     * @param string $id
     *
     * @return \D3jn\Larelastic\Contracts\Models\Searchable|null
     */
    public function find(string $id): ?Searchable
    {
        $params = $this->getCommonParams();

        $params['id'] = $id;

        try {
            $result = $this->client->get($params);
            $searchableID = $this->source->getPrimary($result);

            $searchable = $this->source->getByID($searchableID);
            $searchable->setElasticData($result);

            return $searchable;
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return null;
        } catch (Exception $e) {
            if (App::environment('production')) {
                report($e);

                return null;
            }

            throw $e;
        }
    }

    /**
     * Get raw array response for formed query.
     *
     * @return array
     */
    public function raw(): array
    {
        $params = $this->getCommonParams();

        $this->injectQueryParameters($params);
        $this->injectSortParameters($params);
        $this->injectPaginationParameters($params);
        $this->injectHighlightParameters($params);
        
        return $this->client->search($params);
    }

    /**
     * Get collection of Searchable instances based on formed query.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get(): \Illuminate\Support\Collection
    {
        $result = $this->raw();

        if ($result['hits']['total'] == 0) {
            return collect();
        }

        $hits = [];
        foreach ($result['hits']['hits'] as $hit) {
            $hits[$this->source->getPrimary($hit)] = $hit;
        }

        $searchables = $this->source->getByIDs(array_keys($hits), $this->relations);
        foreach ($searchables as $searchable) {
            $searchable->setElasticData($hits[$searchable->getKey()]);
        }

        return $searchables;
    }

    /**
     * Return paginated collection of Searchable instances based on formed
     * query.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate(?int $perPage = null, string $pageName = 'page', ?int $currentPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?: $this->source->getPerPage();
        $currentPage = $currentPage ?: Paginator::resolveCurrentPage($pageName);
        $options = [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ];

        $total = $this->count();
        $items = $total > 0 
            ? $this->offset(($currentPage - 1) * $perPage)->limit($perPage)->get()
            : collect();

        return app()->makeWith(LengthAwarePaginator::class, compact(
            'items', 'total', 'perPage', 'currentPage', 'options'
        ));
    }

    /**
     * Get count based on formed query
     *
     * @return int
     */
    public function count(): int
    {
        $params = $this->getCommonParams();

        $this->injectQueryParameters($params);

        // Ommit the search results.
        $params['size'] = 0;

        $result = $this->client->search($params);

        return $result['hits']['total'];
    }

    /**
     * Inject query builder query parameters to request params array.
     *
     * @param array &$params
     *
     * @return void
     */
    protected function injectQueryParameters(array &$params)
    {
        if ($this->query !== null) {
            $bool = $this->query->toArray();

            // Null-value indicates that query is not complete and
            // should not be included.
            if ($bool === null) {
                $params['body'] = [];

                return;
            }

            $params['body']['query'] = $bool;
        } else {
            $params['body']['query'] = $this->queryRaw;
        }
    }

    /**
     * Inject query builder sort parameters to request params array.
     *
     * @param array &$params
     *
     * @return void
     */
    protected function injectSortParameters(array &$params)
    {
        if (! empty($this->orderBy)) {
            $params['body']['sort'] = $this->orderBy;
        }
    }

    /**
     * Inject limit/offset query builder parameters to request params array.
     *
     * @param array &$params
     *
     * @return void
     */
    protected function injectPaginationParameters(array &$params)
    {
        if ($this->limit !== null) {
            $params['body']['size'] = $this->limit;

            if ($this->offset !== null) {
                $params['body']['from'] = $this->offset;
            }
        }
    }

    /**
     * Inject highlight query builder parameters to request params array.
     *
     * @param array &$params
     *
     * @return void
     */
    protected function injectHighlightParameters(array &$params)
    {
        if ($this->highlightRaw !== null) {
            $params['body']['highlight'] = $this->highlightRaw;
        } else if ($this->highlight !== null) {
            $raw = $this->highlight->getRaw();

            if (! empty($raw)) {
                $params['body']['highlight'] = $this->highlight->getRaw();
            }
        }
    }

    /**
     * Get array of common parameters for elasticsearch request.
     *
     * @return array
     */
    protected function getCommonParams(): array
    {
        $default = [
            'index' => $this->source->getSearchIndex(),
            'type' => $this->source->getSearchType(),
        ];

        if ($this->requestRaw !== null) {
            return array_merge($default, $this->requestRaw);
        }

        return $default;
    }
}
