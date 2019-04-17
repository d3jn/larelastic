<?php

namespace D3jn\Larelastic;

use D3jn\Larelastic\Console\Commands\IndexCommand;
use D3jn\Larelastic\Query\DefaultLogger;
use D3jn\Larelastic\Resolvers\ConfigIndexResolver;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class LarelasticServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $config = App::make('path.config') . DIRECTORY_SEPARATOR . 'larelastic.php';
        $this->publishes([
            __DIR__ . '/../config/larelastic.php' => $config
        ]);

        $this->commands([IndexCommand::class]);
    }

    /**
     * Registers this package's services.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/larelastic.php',
            'larelastic'
        );

        $this->app->singleton('Elasticsearch\Client', function () {
            return ClientBuilder::create()
                ->setHosts(Config::get('larelastic.hosts'))
                ->build();
        });

        $this->app->bind('larelastic.query-logger', function () {
            return new DefaultLogger;
        });

        $this->app->bind('larelastic.default-index-resolver', function () {
            return new ConfigIndexResolver;
        });

        $this->app->bind('larelastic', Larelastic::class);
    }
}
