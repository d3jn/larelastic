<?php

namespace D3jn\Larelastic\Query;

use D3jn\Larelastic\Contracts\Models\Searchable;
use D3jn\Larelastic\Exceptions\LarelasticException;
use D3jn\Larelastic\Query\Traits\HasDslHelpers;
use Elasticsearch\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Traits\Macroable;

class Builder
{
    use HasDslHelpers, Macroable;

    /**
     * ElasticSearch native PHP client.
     *
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * Raw highlight array parameters.
     *
     * @var array|null
     */
    protected $highlightRaw = null;

    /**
     * Raw result from last executed request from this builder.
     *
     * @var array|null
     */
    protected $lastResult = null;

    /**
     * Limit for results.
     *
     * @var int|null
     */
    protected $limit = null;

    /**
     * Offset for results.
     *
     * @var int|null
     */
    protected $offset = null;

    /**
     * Order by fields.
     *
     * @var array
     */
    protected $orderBy = [];

    /**
     * The relationships that should be eager loaded.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * Raw request for entire query.
     *
     * @var array|null
     */
    protected $requestRaw = null;

    /**
     * Searchable source for type.
     *
     * @var \D3jn\Larelastic\Contracts\Models\Searchable
     */
    protected $source;

    /**
     * Builder constructor.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $source
     * @param \Elasticsearch\Client                        $client
     */
    public function __construct(Searchable $source, Client $client)
    {
        $this->source = $source;
        $this->client = $client;

        // We initialize our request with simple query to simply match all documents of
        // searchable type.
        $this->requestRaw = [
            'query' => ['match_all' => (object) []]
        ];
    }

    /**
     * Add order for query.
     *
     * @param string $field
     * @param string $rule
     *
     * @return \D3jn\Larelastic\Query\Builder
     */
    public function addOrderBy(string $field, string $rule = 'desc'): Builder
    {
        $this->orderBy[] = [$field => $rule];

        return $this;
    }

    /**
     * Get count based on formed query
     *
     * @return int
     */
    public function count(): int
    {
        $params = $this->getCommonParams();

        $this->injectDslParameters($params);

        // Ommit the search results.
        $params['size'] = 0;

        $result = $this->client->search($params);

        return (int) $result['hits']['total'];
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
            $searchableId = $this->source->getPrimary($result);

            $searchable = $this->source->getById($searchableID);
            $searchable->setElasticData($result);

            return $searchable;
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return null;
        } catch (LarelasticException $e) {
            if (App::environment('production')) {
                report($e);

                return null;
            }

            throw $e;
        }
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param int $page
     * @param int $perPage
     *
     * @return \D3jn\Larelastic\Query\Builder
     */
    public function forPage(int $page, int $perPage = 10)
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    /**
     * Get collection of Searchable instances based on formed query.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get(): Collection
    {
        $result = $this->raw();

        if ($result['hits']['total'] == 0) {
            return collect();
        }

        $hits = [];
        foreach ($result['hits']['hits'] as $hit) {
            $hits[$this->source->getPrimary($hit)] = $hit;
        }

        $searchables = $this->source->getByIds(array_keys($hits), $this->relations);
        foreach ($searchables as $searchable) {
            $searchable->setElasticData($hits[$searchable->getKey()]);
        }

        return $searchables;
    }

    /**
     * Get raw result from last executed request from this builder.
     *
     * @param string|null $key
     * @param mixed       $default
     *
     * @return mixed
     */
    public function getLastResultRaw(?string $key = null, $default = null)
    {
        if ($key !== null) {
            return Arr::get($this->lastResult, $key, $default);
        }

        return $this->lastResult;
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
     * Getter for offset.
     *
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->offset;
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
     * Getter for relations.
     *
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * Highlight specified fields in the request.
     *
     * @param array $fields
     * @param array $settings
     *
     * @return \D3jn\Larelastic\Query\Builder
     */
    public function highlight(array $fields, array $settings = []): Builder
    {
        foreach ($fields as &$field) {
            // Empty highlight field clauses must be represented as empty objects.
            // This will guarantee future conversion to a valid query JSON representation.
            if ($field === []) {
                $field = (object) [];
            }
        }

        // Adding fields to the root level of highlight settings.
        $settings['fields'] = $fields;

        return $this->highlightRaw($settings);
    }

    /**
     * Highlight specified fields in the request by raw array of parameters.
     *
     * @param array $params
     *
     * @return \D3jn\Larelastic\Query\Builder
     */
    public function highlightRaw(array $params): Builder
    {
        $this->highlightRaw = $params;

        return $this;
    }

    /**
     * Limit results count.
     *
     * @param int limit
     *
     * @return \D3jn\Larelastic\Query\Builder
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
     * Offset results.
     *
     * @param int offset
     *
     * @return \D3jn\Larelastic\Query\Builder
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

        return new LengthAwarePaginator($items, $total, $perPage, $currentPage, $options);
    }

    /**
     * Get raw array result for query from this builder.
     *
     * @param array $params
     *
     * @return array
     */
    public function raw(array $params = []): array
    {
        $builderParams = $this->getCommonParams();

        $this->injectDslParameters($builderParams);
        $this->injectSortParameters($builderParams);
        $this->injectPaginationParameters($builderParams);
        $this->injectHighlightParameters($builderParams);

        // Priority is given to the parameters passed here directly (if there are any).
        $params = array_merge($builderParams, $params);

        return $this->lastResult = $this->client->search($params);
    }

    /**
     * Set request raw parameters for entire query.
     *
     * Note that some of those fields will be overriden if also featured in
     * query object via other (i.e., DSL) methods.
     *
     * @param array $request
     *
     * @return \D3jn\Larelastic\Query\Builder
     */
    public function requestRaw(array $request): Builder
    {
        $this->requestRaw = $request;

        return $this;
    }

    /**
     * Set the relationships that should be eager loaded.
     *
     * @param string ...$relations
     *
     * @return \D3jn\Larelastic\Query\Builder
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
     * @return \D3jn\Larelastic\Query\Builder
     */
    public function without(string ...$relations)
    {
        $this->relations = array_diff_key($this->relations, $relations);

        return $this;
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
            'type' => $this->source->getSearchType()
        ];

        if ($this->requestRaw !== null) {
            $default['body'] = $this->requestRaw;
        }

        return $default;
    }

    /**
     * Inject DSL builder body parameters to request params array.
     *
     * @param array &$params
     */
    protected function injectDslParameters(array &$params)
    {
        if ($this->dsl !== null) {
            $params['body'] = $this->dsl->toArray();
        }
    }

    /**
     * Inject highlight query builder parameters to request params array.
     *
     * @param array &$params
     */
    protected function injectHighlightParameters(array &$params)
    {
        if ($this->highlightRaw !== null) {
            $params['body']['highlight'] = $this->highlightRaw;
        }
    }

    /**
     * Inject limit/offset query builder parameters to request params array.
     *
     * @param array &$params
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
     * Inject query builder sort parameters to request params array.
     *
     * @param array &$params
     */
    protected function injectSortParameters(array &$params)
    {
        if (! empty($this->orderBy)) {
            $params['body']['sort'] = $this->orderBy;
        }
    }
}
