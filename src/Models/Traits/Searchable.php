<?php

namespace D3jn\Larelastic\Models\Traits;

use Closure;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

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
     * Delete document from Elasticsearch index.
     *
     * @param bool|null $forceRefresh
     */
    public function deleteFromElasticsearch(?bool $forceRefresh = null): void
    {
        if (null === $forceRefresh) {
            $forceRefresh = $this->getElasticsearchRefreshState();
        }

        try {
            App::make('Elasticsearch\Client')->delete([
                'index' => $this->getSearchIndex(),
                'type' => $this->getSearchType(),
                'id' => $this->getSearchKey(),
                'refresh' => $forceRefresh,
            ]);
        } catch (Missing404Exception $e) {
            if (Config::get('larelastic.silent_mode')) {
                report($e);
            } else {
                throw $e;
            }
        }
    }

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
    public function getElasticsearchData(?string $key = null, $default = null)
    {
        if (null !== $key) {
            return Arr::get($this->elasticData, $key, $default);
        }

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
     * document.
     *
     * If $field is not specified then collection of all existing highlighted
     * matches will be returned.
     *
     * @param string|null $field
     *
     * @return array
     */
    public function getHighlight(?string $field = null): array
    {
        if (null === $field) {
            return $this->elasticData['highlight'] ?? [];
        }

        return $this->elasticData['highlight'][$field] ?? [];
    }

    /**
     * Get primary value from Elasticsearch attributes.
     *
     * @return mixed
     */
    public function getPrimary(array $attributes)
    {
        return $attributes['_id'];
    }

    /**
     * Get document field values.
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
            if (null === $only || in_array($key, $only)) {
                $result[$key] = $value instanceof Closure ? $value($this) : $value;
            }
        }

        // Making sure id is always present to identify the record.
        if (!isset($result[$this->getSearchKeyName()])) {
            $result[$this->getSearchKeyName()] = $this->getSearchKey();
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
     * Get type mapping for this document. Returns empty array if no mapping
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
     * Get type settings for this document. Returns empty array if no settings
     * should be specified for the Elasticsearch index.
     *
     * @return array
     */
    public function getTypeSettings(): array
    {
        if (property_exists($this, 'typeSettings')) {
            return $this->typeSettings;
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
     * Sync (create or update) document to Elasticsearch index.
     *
     * @param bool|null  $forceRefresh
     * @param array|null $only
     */
    public function syncToElasticsearch(?bool $forceRefresh = null, ?array $only = null): void
    {
        if (null === $forceRefresh) {
            $forceRefresh = $this->getElasticsearchRefreshState();
        }

        // If only specific keys were requested for syncing...
        if (null !== $only) {
            // ...then we issue partial update.
            App::make('Elasticsearch\Client')->update([
                'index' => $this->getSearchIndex(),
                'type' => $this->getSearchType(),
                'id' => $this->getSearchKey(),
                'body' => [
                    'doc' => $this->getSearchAttributes($only),
                ],
                'refresh' => $forceRefresh,
            ]);
        } else {
            // Otherwise we fully reindex model's respective document.
            App::make('Elasticsearch\Client')->index([
                'index' => $this->getSearchIndex(),
                'type' => $this->getSearchType(),
                'id' => $this->getSearchKey(),
                'body' => $this->getSearchAttributes($only),
                'refresh' => $forceRefresh,
            ]);
        }
    }
}
