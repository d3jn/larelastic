<?php

namespace D3jn\Larelastic\Models\Observers;

use D3jn\Larelastic\Contracts\Models\Searchable;
use Illuminate\Support\Facades\App;
use Elasticsearch\Client;

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
     * Update passed entity when save event is triggered.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $entity
     *
     * @return void
     */
    public function saved(Searchable $entity): void
    {
        $this->client->index([
            'index' => $entity->getSearchIndex(),
            'type' => $entity->getSearchType(),
            'id' => $entity->id,
            'body' => $entity->getSearchAttributes(),
            'refresh' => App::environment('testing')
        ]);
    }

    /**
     * Delete passed entity when delete event is triggered.
     *
     * @param \D3jn\Larelastic\Contracts\Models\Searchable $entity
     *
     * @return void
     */
    public function deleted(Searchable $entity): void
    {
        $this->client->delete([
            'index' => $entity->getSearchIndex(),
            'type' => $entity->getSearchType(),
            'id' => $entity->id,
            'refresh' => App::environment('testing')
        ]);
    }
}
