<?php

namespace D3jn\Larelastic\Models\Traits;

use Illuminate\Support\Collection;

trait IntegratesWithEloquent
{
    /**
     * Get searchable instance by specified id or null if not found.
     *
     * @param mixed $id
     *
     * @return \D3jn\Larelastic\Contracts\Models\Searchable|null
     */
    public function getById($id): ?\D3jn\Larelastic\Contracts\Models\Searchable
    {
        return (new static())->find($id);
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

        if (!empty($relations)) {
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
     * Get the number of searchables to return per page.
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->getPerPage();
    }

    /**
     * Get partial update map for this searchable fields.
     *
     * @return array
     */
    public function getPartialUpdateMapForElasticsearch(): array
    {
        if (property_exists($this, 'partialUpdateMap')) {
            return $this->partialUpdateMap;
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
        return (new static())->orderBy($this->getKeyName());
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
     * Return primary key name for searchable entity.
     *
     * @return string
     */
    public function getSearchKeyName(): string
    {
        return $this->getKeyName();
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
     * Pass through searchable entities of this type with a given callback.
     *
     * @param callable $callback
     */
    public function walkSearchableEntities(callable $callback)
    {
        $query = $this->getSearchableEntitiesQuery();
        if (property_exists($this, 'walkSearchableWith') && !empty($this->walkSearchableWith)) {
            $query->with($this->walkSearchableWith);
        }

        $query->chunk(1000, function ($entities) use ($callback) {
            $callback($entities);
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
