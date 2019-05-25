<?php

namespace D3jn\Larelastic\Contracts\Models;

interface Searchable
{
    /**
     * Delete document from Elasticsearch index.
     *
     * @param bool|null $forceRefresh
     */
    public function deleteFromElasticsearch(?bool $forceRefresh = null): void;

    /**
     * Get searchable instance by specified id or null if not found.
     *
     * @param mixed $id
     *
     * @return \D3jn\Larelastic\Contracts\Models\Searchable|null
     */
    public function getById($id): ?Searchable;

    /**
     * Get collection searchable instances by specified ids.
     *
     * @param array $ids
     * @param array $relations
     *
     * @return \Illuminate\Support\Collection
     */
    public function getByIds(array $ids, array $relations = []): \Illuminate\Support\Collection;

    /**
     * Get array of attribute values or single value by key from Elasticsearch result version of this instance.
     *
     * Returns null if Elasticsearch counterpart wasn't assigned to this entity.
     *
     * @param string|null $key
     * @param mixed       $default
     *
     * @return mixed
     */
    public function getElasticsearchData(?string $key = null, $default = null);

    /**
     * Get refresh option value for sync queries.
     *
     * @return bool
     */
    public function getElasticsearchRefreshState(): bool;

    /**
     * Get highlight match for field if present within elastic data for this
     * document.
     *
     * If $field is not specified then array of all existing highlighted
     * matches will be returned.
     *
     * @param string|null $field
     *
     * @return array
     */
    public function getHighlight(?string $field = null): array;

    /**
     * Get the number of Searchables to return per page.
     *
     * @return int
     */
    public function getPageSize(): int;

    /**
     * Get primary value from Elasticsearch attributes of this instance.
     *
     * @return mixed
     */
    public function getPrimary(array $attributes);

    /**
     * Get document field values.
     *
     * @return array
     */
    public function getSearchAttributes(): array;

    /**
     * Return index name for this searchable entity.
     *
     * @return string
     */
    public function getSearchIndex(): string;

    /**
     * Return primary key for searchable entity.
     *
     * @return string
     */
    public function getSearchKey(): string;

    /**
     * Return primary key name for searchable entity.
     *
     * @return string
     */
    public function getSearchKeyName(): string;

    /**
     * Return index name for this searchable entity.
     *
     * @return string
     */
    public function getSearchType(): string;

    /**
     * Get type mapping for this document. Returns empty array if no mapping
     * should be specified for the type in Elasticsearch index.
     *
     * @return array
     */
    public function getTypeMapping(): array;

    /**
     * Get type settings for this document. Returns empty array if no settings
     * should be specified for the Elasticsearch index.
     *
     * @return array
     */
    public function getTypeSettings(): array;

    /**
     * Attach attribute values from Elasticsearch version of this instance.
     */
    public function setElasticsearchData(array $attributes): void;

    /**
     * Set refresh option value for sync queries.
     *
     * @param bool $refresh
     */
    public function setElasticsearchRefreshState(bool $refresh): void;

    /**
     * Sync (create or update) document to Elasticsearch index.
     *
     * @param bool|null  $forceRefresh
     * @param array|null $only
     */
    public function syncToElasticsearch(?bool $forceRefresh = null, ?array $only = null): void;

    /**
     * Pass through all searchable entities of this type with a given callback.
     *
     * @param Callable $callback
     */
    public function walkSearchableEntities(callable $callback);
}
