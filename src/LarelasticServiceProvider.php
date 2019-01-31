<?php

namespace D3jn\Larelastic;

use D3jn\Larelastic\Console\Commands\IndexCommand;
use D3jn\Larelastic\Contracts\IndexResolver;
use D3jn\Larelastic\Query\Factory;
use Illuminate\Support\ServiceProvider;
use Elasticsearch\ClientBuilder;

class LarelasticServiceProvider extends ServiceProvider implements IndexResolver
{
    /**
     * Get index name for specified type.
     *
     * @param  string $type
     * @return string
     */
    public function resolveIndexForType($type): string
    {
        $index = config("larelastic.type_indices.{$type}");

        if (empty($index)) {
            return config('larelastic.default_index');
        }

        return $index;
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/larelastic.php' => config_path('larelastic.php')
        ]);

        $this->commands([
            IndexCommand::class
        ]);
    }

    /**
     * Registers this package's services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/larelastic.php',
            'larelastic'
        );

        $this->app->singleton('Elasticsearch\Client', function () {
            return ClientBuilder::create()
                ->setHosts(config('larelastic.hosts'))
                ->build();
        });

        $this->app->singleton('D3jn\Larelastic\Contracts\IndexResolver', function () {
            return $this;
        });

        $this->app->singleton('D3jn\Larelastic\Query\Factory', Factory::class);

        $this->app->bind('larelastic', Larelastic::class);
    }
}
