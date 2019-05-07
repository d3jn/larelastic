<?php

namespace D3jn\Larelastic\Models\Observers;

use D3jn\Larelastic\Contracts\Models\Searchable;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\SoftDeletes;

class SearchableObserver
{
    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * SearchableObserver constructor.
     *
     * @param \Elasticsearch\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Delete passed entity when delete event is triggered.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $entity
     */
    public function deleted(Searchable $entity): void
    {
        $entity->deleteFromElasticsearch();
    }

    /**
     * Restore passed entity when restore event is triggered.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $entity
     */
    public function restore(Searchable $entity): void
    {
        if ($this->shouldBeOmittedFromElasticsearch($entity)) {
            return;
        }

        $entity->syncToElasticsearch();
    }

    /**
     * Update passed entity when save event is triggered.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $entity
     */
    public function saved(Searchable $entity): void
    {
        if ($this->shouldBeOmittedFromElasticsearch($entity)) {
            return;
        }

        // Trashed models shouldn't exist in our index, so we simply ignore save event for them.
        if ($this->usesSoftDeleting($entity) && $entity->trashed()) {
            return;
        }

        $entity->syncToElasticsearch();
    }

    /**
     * Check if model should be omitted from Elasticsearch.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $entity
     *
     * @return bool
     */
    protected function shouldBeOmittedFromElasticsearch(Searchable $entity): bool
    {
        if (method_exists($entity, 'shouldBeOmittedFromElasticsearch')) {
            return $entity->shouldBeOmittedFromElasticsearch();
        }

        return false;
    }

    /**
     * Check if searchable instance utilizes SoftDeletes trait.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $entity
     *
     * @return bool
     */
    protected function usesSoftDeleting(Searchable $entity): bool
    {
        return in_array(SoftDeletes::class, class_uses($entity));
    }
}
