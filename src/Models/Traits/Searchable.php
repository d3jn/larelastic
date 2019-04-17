<?php

namespace D3jn\Larelastic\Models\Traits;

use Closure;
use D3jn\Larelastic\Models\Observers\SearchableObserver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

/**
 * This trait contains default Eloquent model implementation of
 * D3jn\Larelastic\Contracts\Models\Searchable contract.
 */
trait Searchable
{
    /**
     * Whether to force Elasticsearch refresh.
     *
     * @var bool
     */
    protected $defaultElasticsearchRefreshState = false;

    /**
     * Elasticsearch data.
     *
     * @var array
     */
    protected $elasticData = null;

    /**
     * Attach our observer to searchable entities using this trait.
     */
    public static function bootSearchable()
    {
        if (Config::get('larelastic.observe_searchable_models', true)) {
            static::observe(SearchableObserver::class);
        }
    }

    /**
     * Delete model from Elasticsearch index.
     *
     * @param bool|null $forceRefresh
     */
    public function deleteFromElasticsearch(?bool $forceRefresh = null): void
    {
        if ($forceRefresh === null) {
            $forceRefresh = $this->getElasticsearchRefreshState();
        }

        App::make('Elasticsearch\Client')->delete([
            'index' => $this->getSearchIndex(),
            'type' => $this->getSearchType(),
            'id' => $this->getSearchKey(),
            'refresh' => $forceRefresh
        ]);
    }

    /**
     * Get searchable instance by specified id or null if not found.
     *
     * @param mixed $id
     *
     * @return \D3jn\Larelastic\Contracts\Models\Searchable|null
     */
    public function getById($id): ?\D3jn\Larelastic\Contracts\Models\Searchable
    {
        return (new static)->find($id);
    }

    /**
     * Get collection searchable instances by specified ids.
     *
     * @param array $ids
     * @param array $relations
     *
     * @return \Illuminate\Support\Collection
     */
    public function getByIds(array $ids, array $relations = []): \Illuminate\Support\Collection
    {
        $query = static::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        if ($this->keyType = 'string') {
            // Making sure string keys are escaped properly.
            $orderById = implode(', ', array_map(function ($value) {
                return $this->getConnection()->getPdo()->quote($value);
            }, $ids));
        } else {
            $orderById = implode(', ', $ids);
        }

        return $query->whereIn('id', $ids)
            ->orderByRaw("field(id, $orderById) asc")
            ->get();
    }

    /**
     * Get attribute values from Elasticsearch version of this instance.
     *
     * Returns null if Elasticsearch counterpart wasn't assigned to this
     * entity.
     *
     * @return array|null
     */
    public function getElasticsearchData(): ?array
    {
        return $this->elasticData;
    }

    /**
     * Get refresh option value for sync queries.
     *
     * @return bool
     */
    public function getElasticsearchRefreshState(): bool
    {
        if (App::environment('testing')) {
            return true;
        }

        if (property_exists($this, 'forceElasticsearchRefresh')) {
            return $this->forceElasticsearchRefresh;
        }

        return $this->defaultElasticsearchRefreshState;
    }

    /**
     * Get highlight match for field if present within Elasticsearch data for this
     * searchable entity.
     *
     * If $field is not specified then collection of all existing highlighted
     * matches will be returned.
     *
     * @param string|null $field
     *
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
     * Get primary value from Elasticsearch attributes of this instance.
     *
     * @return mixed
     */
    public function getPrimary(array $attributes)
    {
        return $attributes['_id'];
    }

    /**
     * Get all searchable entities query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getSearchableEntitiesQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return (new static)->orderBy($this->getKeyName());
    }

    /**
     * Get searchable field values for this entity.
     *
     * @param array|null $only
     *
     * @return array
     */
    public function getSearchAttributes(?array $only = null): array
    {
        $searchArray = $this->toSearchArray();

        $result = [];
        foreach ($searchArray as $key => $value) {
            if ($only === null || in_array($key, $only)) {
                $result[$key] = $value instanceof Closure ? $value($this) : $value;
            }
        }

        // Making sure id is always present to identify the record.
        if (! isset($result[$this->getKeyName()])) {
            $result[$this->getKeyName()] = $this->getKey();
        }

        return $result;
    }

    /**
     * Return index name for this searchable entity.
     *
     * @return string
     */
    public function getSearchIndex(): string
    {
        $index = property_exists($this, 'searchIndex') ? $this->searchIndex : null;
        $type = $this->getSearchType();

        return App::make('larelastic.default-index-resolver')->resolveIndexForType($type, $index);
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
     * Get mapping for this searchable entity. Returns empty array if no mapping
     * should be specified for the type in Elasticsearch index.
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
     * Attach attribute values from Elasticsearch version of this instance.
     */
    public function setElasticsearchData(array $attributes): void
    {
        $this->elasticData = $attributes;
    }

    /**
     * Get refresh option value for sync queries.
     *
     * @param bool $refresh
     */
    public function setElasticsearchRefreshState(bool $refresh): void
    {
        if (property_exists($this, 'forceElasticsearchRefresh')) {
            $this->forceElasticsearchRefresh = $refresh;
        }

        $this->defaultElasticsearchRefreshState = $refresh;
    }

    /**
     * Sync (create or update) searchable entity to Elasticsearch index.
     *
     * @param bool|null  $forceRefresh
     * @param array|null $only
     */
    public function syncToElasticsearch(?bool $forceRefresh = null, ?array $only = null): void
    {
        if ($forceRefresh === null) {
            $forceRefresh = $this->getElasticsearchRefreshState();
        }

        // If only specific keys were requested for syncing...
        if ($only !== null) {
            // ...then we issue partial update.
            App::make('Elasticsearch\Client')->update([
                'index' => $this->getSearchIndex(),
                'type' => $this->getSearchType(),
                'id' => $this->getSearchKey(),
                'body' => [
                    'doc' => $this->getSearchAttributes($only)
                ],
                'refresh' => $forceRefresh
            ]);
        } else {
            // Otherwise we fully reindex model's respective document.
            App::make('Elasticsearch\Client')->index([
                'index' => $this->getSearchIndex(),
                'type' => $this->getSearchType(),
                'id' => $this->getSearchKey(),
                'body' => $this->getSearchAttributes($only),
                'refresh' => $forceRefresh
            ]);
        }
    }

    /**
     * Pass through searchable entities of this type with a given callback.
     *
     * @param Callable $callback
     */
    public function walkSearchableEntities(callable $callback)
    {
        $query = $this->getSearchableEntitiesQuery();
        if (property_exists($this, 'walkSearchableWith') && ! empty($this->walkSearchableWith)) {
            $query->with($this->walkSearchableWith);
        }

        $query->chunk(100, function ($entities) use ($callback) {
            foreach ($entities as $entity) {
                $callback($entity);
            }
        });
    }

    /**
     * Serialize current model instance into array for it's type.
     *
     * @return array
     */
    protected function toSearchArray(): array
    {
        return $this->toArray();
    }
}
