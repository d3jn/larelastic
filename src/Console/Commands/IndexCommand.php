<?php

namespace D3jn\Larelastic\Console\Commands;

use D3jn\Larelastic\Contracts\Models\Searchable;
use Illuminate\Console\Command;
use Elasticsearch\Client;

class IndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larelastic:index {--refresh} {--drop-only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '(Re)create indices for all registered entities and bulk them there';

    /**
     * @var Client
     */
    protected $client;

    /**
     * Drop existing indices.
     *
     * @param  array $indices
     * @return void
     */
    protected function deleteExistingIndices(array $indices): void
    {
        $deletedCount = 0;
        foreach ($indices as $indexName => $index) {
            if ($this->client->indices()->exists(['index' => $indexName])) {
                $this->info("Found existing <{$indexName}> index! Deleting...");
                $this->client->indices()->delete(['index' => $indexName]);

                $deletedCount++;
            }
        }

        $this->info("Successfully deleted {$deletedCount} indices.");
    }

    /**
     * Create new indices, drop existing ones.
     *
     * @param  array $indices
     * @return void
     */
    protected function createNewIndices(array $indices): void
    {
        foreach ($indices as $indexName => $index) {
            $this->info("Creating new index <{$indexName}>...");

            $data = ['index' => $indexName];
            if (! empty($index['mappings'])) {
                $data['body']['mappings'] = $index['mappings'];
            }
            $this->client->indices()->create($data);
        }
    }

    /**
     * Import type entities. Return number of imported records.
     *
     * @param  array $typeEntities
     * @return int
     */
    protected function importTypeEntities(array $typeEntities): int
    {
        $this->info("Starting importing process...");
        $bar = $this->output->createProgressBar(count($typeEntities));
        $bar->setFormat(
            "%current%/%max% [%bar%] %percent:3s%% " . PHP_EOL
            . "Time passed:\t%elapsed%" . PHP_EOL
            . "Importing:\t%message%"
        );

        $count = 0;
        foreach ($typeEntities as $class => $entity) {
            $bar->setMessage($class);
            $bar->advance();

            $bulkEntities = [];
            $walkCallback = function (Searchable $searchable) use (&$bulkEntities, &$count, $entity) {
                $bulkEntities[] = [
                    'index' => [
                        '_index' => $searchable->getSearchIndex(),
                        '_type' => $searchable->getSearchType(),
                        '_id' => $searchable->getSearchKey()
                    ]
                ];

                $bulkEntities[] = $searchable->getSearchAttributes();
                $count++;
            };
            $entity->walkSearchableEntities($walkCallback);

            if (! empty($bulkEntities)) {
                $this->client->bulk([
                    'refresh' => $this->option('refresh'),
                    'body' => $bulkEntities
                ]);
            }
        }

        $bar->setMessage('finished');
        $bar->finish();

        return $count;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Resolving elasticsearch client.
        $this->client = resolve(Client::class);

        $this->info("Reading configuration...");

        // Preparing data.
        $types = config('larelastic.types');
        $typeEntities = [];
        $indices = [];
        $typeCount = 0;
        foreach ($types as $class) {
            if (! in_array(Searchable::class, class_implements($class))) {
                $this->error("Class <{$class}> does not implement Searchable contract and will be skipped!");
                continue;
            }
            $typeCount++;

            // Store instance of Searchable entity and retrieve needed info
            // about it's index, type and mapping.

            /** @var Searchable $entity */
            $entity = new $class;
            $index = $entity->getSearchIndex();
            $type = $entity->getSearchType();
            $mapping = $entity->getTypeMapping();

            // Placing it all in more convinient structure.
            $indices[$index]['types'][$type] = $type;
            if (! empty($mapping)) {
                $indices[$index]['mappings'][$type] = $mapping;
            }
            $typeEntities[$class] = $entity;
        }

        $indexCount = count($indices);
        $this->info(
            "Successfully found configuration for {$typeCount} types in {$indexCount} indices."
        );

        $this->deleteExistingIndices($indices);
        if (! $this->option('drop-only')) {
            $this->createNewIndices($indices);
            $count = $this->importTypeEntities($typeEntities);

            $this->info(PHP_EOL . "Reindexing successfully ended! Imported {$count} records in total.");
        }
    }
}
