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
     * Update passed entity when created event is triggered.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $entity
     */
    public function created(Searchable $entity): void
    {
        if ($this->shouldBeOmittedFromElasticsearch($entity)) {
            return;
        }

        $entity->syncToElasticsearch();
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
     * Update passed entity when updated event is triggered.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $entity
     */
    public function updated(Searchable $entity): void
    {
        if ($this->shouldBeOmittedFromElasticsearch($entity)) {
            return;
        }

        // If partial update succeeded then we are done here.
        if ($this->tryPartialUpdate($entity)) {
            return;
        }

        // ... else we do a complete sync.
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

        // Trashed models shouldn't exist in our index, so we simply ignore save event for them.
        if ($this->usesSoftDeleting($entity) && $entity->trashed()) {
            return true;
        }

        return false;
    }

    /**
     * Check if this entity is eligible for partial update. If so then do partial update and return true.
     * Return false if partial update won't be sufficient enough and do nothing.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $entity
     *
     * @return bool
     */
    protected function tryPartialUpdate(Searchable $entity): bool
    {
        if (! method_exists($entity, 'getPartialUpdateMapForElasticsearch')) {
            return false;
        }

        $map = $entity->getPartialUpdateMapForElasticsearch();
        if (empty($map)) {
            return false;
        }

        $dirty = $entity->getDirty();

        // If not all dirty columns are present in the map then we can't do a partial update.
        if (count(array_diff_key($dirty, $map)) > 0) {
            return false;
        }

        // Now we are sure that partial update will be sufficient enough based on model's partial update map,
        // so we retrieve fields specified there for this model's dirty attributes.
        $dirtyAttributes = array_keys($dirty);
        $fields = array_values(array_filter(
            $map,
            function ($attribute) use ($dirtyAttributes) {
                return in_array($attribute, $dirtyAttributes);
            },
            ARRAY_FILTER_USE_KEY
        ));

        // Lastly we flatten the fields array (in case one attribute triggers update of multiple fields
        // and has array of fields as it's map value).
        $only = [];
        foreach ($fields as $field) {
            if (is_array($field)) {
                $only = array_merge($only, array_values($field));
            } else {
                $only[] = $field;
            }
        }

        $entity->syncToElasticsearch(null, array_unique($only));

        return true;
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
