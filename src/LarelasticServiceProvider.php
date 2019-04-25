<?php

namespace D3jn\Larelastic;

use D3jn\Larelastic\Console\Commands\IndexCommand;
use D3jn\Larelastic\Events\BuilderElasticsearchRequestExecuted;
use D3jn\Larelastic\Query\DefaultLogger;
use D3jn\Larelastic\Resolvers\ConfigIndexResolver;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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

        // Logging.
        if (config('larelastic.logging.enabled', false)) {
            // Register event listeners.
            Event::listen('D3jn\Larelastic\Events\BuilderElasticsearchRequestExecuted', function (BuilderElasticsearchRequestExecuted $event) {
                Log::channel(config('larelastic.logging.channel', null))->info(
                    'Builder Elasticsearch request executed!',
                    ['parameters' => $event->parameters, 'result' => $event->result]
                );
            });
        }
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
