<?php

namespace D3jn\Larelastic\Contracts\Models;

use D3jn\Larelastic\Contracts\Models\Searchable;

interface Searchable
{
    /**
     * Return index name for this searchable entity.
     *
     * @return string
     */
    public function getSearchIndex(): string;
    
    /**
     * Return index name for this searchable entity.
     *
     * @return string
     */
    public function getSearchType(): string;
    
    /**
     * Return primary key for searchable entity.
     *
     * @return string
     */
    public function getSearchKey(): string;
    
    /**
     * Get searchable field values for this entity.
     *
     * @return array
     */
    public function getSearchAttributes(): array;
    
    /**
     * Get mapping for this searchable entity. Returns empty array if no mapping
     * should be specified for the type in elasticsearch index.
     *
     * @return array
     */
    public function getTypeMapping(): array;

    /**
     * Get all searchable entities query.
     *
     * @return mixed
     */
    public function getSearchableEntitiesQuery(): \Illuminate\Database\Eloquent\Builder;

    /**
     * Pass through all searchable entities of this type with a given callback.
     *
     * @param Callable $callback
     */
    public function walkSearchableEntities(Callable $callback);

    /**
     * Get primary value from elasticsearch attributes of this instance.
     *
     * @return mixed
     */
    public function getPrimary(array $attributes);

    /**
     * Get searchable instance by specified id or null if not found.
     *
     * @param mixed $id
     *
     * @return \D3jn\Larelastic\Contracts\Models\Searchable|null
     */
    public function getByID($id): ?Searchable;

    /**
     * Get collection searchable instances by specified ids.
     *
     * @param array $ids
     * @param array $relations
     *
     * @return \Illuminate\Support\Collection
     */
    public function getByIDs(array $ids, array $relations = []): \Illuminate\Support\Collection;

    /**
     * Attach attribute values from elasticsearch version of this instance.
     *
     * @return void
     */
    public function setElasticData(array $attributes);

    /**
     * Get attribute values from elasticsearch version of this instance.
     *
     * Returns null if elasticsearch counterpart wasn't assigned to this
     * entity.
     *
     * @return array|null
     */
    public function getElasticData(): ?array;

    /**
     * Get highlight match for field if present within elastic data for this
     * searchable entity.
     *
     * If $field is not specified then array of all existing highlighted
     * matches will be returned.
     *
     * @param string|null $field
     *
     * @return mixed
     */
    public function getHighlight(?string $field);

    /**
     * Get the number of Searchables to return per page.
     *
     * @return int
     */
    public function getPerPage();

    /**
     * Get refresh option value for sync queries.
     *
     * @return mixed
     */
    public function getRefreshState();

    /**
     * Set refresh option value for sync queries.
     *
     * @param mixed $refresh
     *
     * @return void
     */
    public function setRefreshState($refresh): void;

    /**
     * Sync model to elastic.
     *
     * @return void
     */
    public function updateInElastic(): void;

    /**
     * Remove model from elastic.
     *
     * @return void
     */
    public function deleteFromElastic(): void;
}
