<?php

namespace D3jn\Larelastic\Models\Traits;

use Illuminate\Support\Facades\App;
use D3jn\Larelastic\Models\Observers\SearchableObserver;
use Illuminate\Support\Facades\DB;

/**
 * This trait contains default Eloquent model implementation of
 * D3jn\Larelastic\Contracts\Models\Searchable contract.
 */
trait Searchable
{
    /**
     * Elasticsearch data.
     *
     * @var array
     */
    protected $elasticData = null;

    /**
     * Default refresh value.
     *
     * @var mixed
     */
    protected $defaultElasticRefresh = false;

    /**
     * Attach our observer to searchable entities using this trait.
     */
    public static function bootSearchable()
    {
        if (config('larelastic.enabled', true)) {
            static::observe(SearchableObserver::class);
        }
    }

    /**
     * Return index name for this searchable entity.
     *
     * @return string
     */
    public function getSearchIndex(): string
    {
        if (property_exists($this, 'searchIndex')) {
            return $this->searchIndex;
        }

        return app('D3jn\Larelastic\Contracts\IndexResolver')->resolveIndexForType(
            $this->getSearchType()
        );
    }

    /**
     * Return index name for this searchable entity.
     *
     * @return string
     */
    public function getSearchType(): string
    {
        if (property_exists($this, 'searchType')) {
            return $this->searchType;
        }

        return $this->getTable();
    }

    /**
     * Return primary key for searchable entity.
     *
     * @return string
     */
    public function getSearchKey(): string
    {
        return $this->getKey();
    }

    /**
     * Get searchable field values for this entity.
     *
     * @return array
     */
    public function getSearchAttributes(): array
    {
        $result = method_exists($this, 'toSearchArray')
            ? $this->toSearchArray()
            : $this->toArray();

        if (! isset($result[$this->getKeyName()])) {
            $result[$this->getKeyName()] = $this->getKey();
        }

        return $result;
    }

    /**
     * Get mapping for this searchable entity. Returns empty array if no mapping
     * should be specified for the type in elasticsearch index.
     *
     * @return array
     */
    public function getTypeMapping(): array
    {
        if (property_exists($this, 'typeMapping')) {
            return $this->typeMapping;
        }

        return [];
    }

    /**
     * Get all searchable entities query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getSearchableEntitiesQuery(): \Illuminate\Database\Eloquent\Builder
    {
        if (method_exists($this, 'overrideGetSearchableEntities')) {
            return $this->overrideGetSearchableEntities();
        }

        return (new static)->orderBy($this->getKeyName());
    }

    /**
     * Pass through searchable entities of this type with a given callback.
     *
     * @param Callable $callback
     */
    public function walkSearchableEntities(callable $callback)
    {
        if (method_exists($this, 'overrideWalkSearchableEntities')) {
            $this->customWalkSearchableEntities($callback);
        } else {
            // Default implementation.
            $this->getSearchableEntitiesQuery()->chunk(100, function ($entities) use ($callback) {
                foreach ($entities as $entity) {
                    $callback($entity);
                }
            });
        }
    }

    /**
     * Get primary value from elasticsearch attributes of this instance.
     *
     * @return mixed
     */
    public function getPrimary(array $attributes)
    {
        return $attributes['_source'][$this->getKeyName()];
    }

    /**
     * Get searchable instance by specified id or null if not found.
     *
     * @param mixed $id
     * @return \D3jn\Larelastic\Contracts\Models\Searchable|null
     */
    public function getByID($id): ?\D3jn\Larelastic\Contracts\Models\Searchable
    {
        return (new static)->find($id);
    }

    /**
     * Get collection searchable instances by specified ids.
     *
     * @param array $ids
     * @param array $relations
     * @return \Illuminate\Support\Collection
     */
    public function getByIDs(array $ids, array $relations = []): \Illuminate\Support\Collection
    {
        $query = (new static)->query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        $orderByID = implode(', ', $ids);

        return $query->whereIn('id', $ids)
            ->orderByRaw(DB::raw("FIELD(id, $orderByID)"))
            ->get();
    }

    /**
     * Attach attribute values from elasticsearch version of this instance.
     *
     * @return void
     */
    public function setElasticData(array $attributes): void
    {
        $this->elasticData = $attributes;
    }

    /**
     * Get attribute values from elasticsearch version of this instance.
     *
     * Returns null if elasticsearch counterpart wasn't assigned to this
     * entity.
     *
     * @return array|null
     */
    public function getElasticData(): ?array
    {
        return $this->elasticData;
    }

    /**
     * Get highlight match for field if present within elastic data for this
     * searchable entity.
     *
     * If $field is not specified then collection of all existing highlighted
     * matches will be returned.
     *
     * @param string|null $field
     * @return \Illuminate\Support\Collection
     */
    public function getHighlight(?string $field): \Illuminate\Support\Collection
    {
        if (! isset($field)) {
            return isset($this->elasticData['highlight'])
                ? collect($this->elasticData['highlight'])
                : collect();
        }

        return isset($this->elasticData['highlight'][$field])
            ? collect($this->elasticData['highlight'][$field])
            : collect();
    }

    /**
     * Get refresh option value for sync queries.
     *
     * @param string $action
     * @return mixed
     */
    public function getRefreshState()
    {
        if (App::environment('testing')) {
            return true;
        }

        if (property_exists($this, 'elasticsearchRefresh')) {
            return $this->elasticsearchRefresh;
        }

        return $this->defaultElasticRefresh;
    }

    /**
     * Get refresh option value for sync queries.
     *
     * @param mixed $refresh
     * @return void
     */
    public function setRefreshState($refresh): void
    {
        if (property_exists($this, 'elasticsearchRefresh')) {
            $this->elasticsearchRefresh = $refresh;
        }

        $this->defaultElasticRefresh = $refresh;
    }

    /**
     * Sync model to elastic.
     *
     * @return void
     */
    public function updateInElastic(): void
    {
        app('Elasticsearch\Client')->index([
            'index' => $this->getSearchIndex(),
            'type' => $this->getSearchType(),
            'id' => $this->id,
            'body' => $this->getSearchAttributes(),
            'refresh' => $this->getRefreshState()
        ]);
    }

    /**
     * Remove model from elastic.
     *
     * @return void
     */
    public function deleteFromElastic(): void
    {
        app('Elasticsearch\Client')->delete([
            'index' => $this->getSearchIndex(),
            'type' => $this->getSearchType(),
            'id' => $this->id,
            'refresh' => $this->getRefreshState()
        ]);
    }
}
