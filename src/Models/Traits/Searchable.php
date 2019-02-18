<?php

namespace D3jn\Larelastic\Models\Traits;

use D3jn\Larelastic\Models\Observers\SearchableObserver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
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
     * Whether to force Elasticsearch refresh.
     *
     * @var bool
     */
    protected $defaultElasticRefreshState = false;

    /**
     * Attach our observer to searchable entities using this trait.
     */
    public static function bootSearchable()
    {
        if (Config::get('larelastic.enabled', true)) {
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
        $index = property_exists($this, 'searchIndex') ? $this->searchIndex : null;
        $type = $this->getSearchType();

        return App::make('larelastic.default-index-resolver')->resolveIndexForType($type, $index);
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
     * Serialize current model instance into array for it's type.
     *
     * @return array
     */
    protected function toSearchArray(): array
    {
        return $this->toArray();
    }

    /**
     * Get searchable field values for this entity.
     *
     * @return array
     */
    public function getSearchAttributes(): array
    {
        $result = $this->toSearchArray();
        if (! isset($result[$this->getKeyName()])) {
            $result[$this->getKeyName()] = $this->getKey();
        }

        return $result;
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
     * Get all searchable entities query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getSearchableEntitiesQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return (new static)->orderBy($this->getKeyName());
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
     * Get primary value from Elasticsearch attributes of this instance.
     *
     * @return mixed
     */
    public function getPrimary(array $attributes)
    {
        return $attributes['_id'];
    }

    /**
     * Get searchable instance by specified id or null if not found.
     *
     * @param mixed $id
     *
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
     *
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
     * Attach attribute values from Elasticsearch version of this instance.
     */
    public function setElasticData(array $attributes): void
    {
        $this->elasticData = $attributes;
    }

    /**
     * Get attribute values from Elasticsearch version of this instance.
     *
     * Returns null if Elasticsearch counterpart wasn't assigned to this
     * entity.
     *
     * @return array|null
     */
    public function getElasticData(): ?array
    {
        return $this->elasticData;
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
     * Get refresh option value for sync queries.
     *
     * @return bool
     */
    public function getElasticRefreshState(): bool
    {
        if (App::environment('testing')) {
            return true;
        }

        if (property_exists($this, 'forceElasticRefresh')) {
            return $this->forceElasticRefresh;
        }

        return $this->defaultElasticRefreshState;
    }

    /**
     * Get refresh option value for sync queries.
     *
     * @param bool $refresh
     */
    public function setElasticRefreshState(bool $refresh): void
    {
        if (property_exists($this, 'forceElasticRefresh')) {
            $this->forceElasticRefresh = $refresh;
        }

        $this->defaultElasticRefreshState = $refresh;
    }

    /**
     * Sync (create or update) searchable entity to Elasticsearch index.
     *
     * @param bool|null $forceRefresh
     */
    public function syncToElastic(?bool $forceRefresh = null): void
    {
        if ($forceRefresh === null) {
            $forceRefresh = $this->getElasticRefreshState();
        }

        App::make('Elasticsearch\Client')->index([
            'index' => $this->getSearchIndex(),
            'type' => $this->getSearchType(),
            'id' => $this->id,
            'body' => $this->getSearchAttributes(),
            'refresh' => $forceRefresh
        ]);
    }

    /**
     * Delete model from Elasticsearch index.
     *
     * @param bool|null $forceRefresh
     */
    public function deleteFromElastic(?bool $forceRefresh = null): void
    {
        if ($forceRefresh === null) {
            $forceRefresh = $this->getElasticRefreshState();
        }

        App::make('Elasticsearch\Client')->delete([
            'index' => $this->getSearchIndex(),
            'type' => $this->getSearchType(),
            'id' => $this->id,
            'refresh' => $forceRefresh
        ]);
    }
}
