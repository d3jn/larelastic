<?php

namespace D3jn\Larelastic\Console\Commands;

use D3jn\Larelastic\Contracts\Models\Searchable;
use Elasticsearch\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class IndexCommand extends Command
{
    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '(Re)create indices for all registered entities and bulk them there';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larelastic:index {--refresh} {--drop-only} {--no-progress}';

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
        $types = Config::get('larelastic.types');
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
            $settings = $entity->getTypeSettings();

            // Placing it all in more convinient structure.
            $indices[$index]['types'][$type] = $type;
            if (! empty($mapping)) {
                $indices[$index]['mappings'][$type] = $mapping;
            }
            if (! empty($settings)) {
                $indices[$index]['settings'] = $settings;
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

    /**
     * Create new indices, drop existing ones.
     *
     * @param array $indices
     */
    protected function createNewIndices(array $indices): void
    {
        foreach ($indices as $indexName => $index) {
            $this->info("Creating new index <{$indexName}>...");

            $data = ['index' => $indexName];
            if (! empty($index['mappings'])) {
                $data['body']['mappings'] = $index['mappings'];
            }
            if (! empty($index['settings'])) {
                $data['body']['settings'] = $index['settings'];
            }

            $this->client->indices()->create($data);
        }
    }

    /**
     * Drop existing indices.
     *
     * @param array $indices
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
     * Import type entities. Return number of imported records.
     *
     * @param array $typeEntities
     *
     * @return int
     */
    protected function importTypeEntities(array $typeEntities): int
    {
        $this->info("Starting importing process...");

        $bar = $this->option('no-progress')
            ? new \Illuminate\Support\Optional(null)
            : $this->output->createProgressBar(count($typeEntities));
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
}
