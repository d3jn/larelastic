# Larelastic

Another take on integrating Elasticsearch into Laravel.

## Getting Started

### Prerequisites

This package was developed using Laravel 5.6. As of now older versions are not tested/supported.  You will also need a running Elasticsearch service of version >6.0.

### Installing

Use composer to install this package:

```
composer require d3jn/larelastic
```

`Laravel Package Auto-Discovery` should handle adding service provider for you automatically or you can manually add it to your providers list in ```app.php```:

```php
'providers' => [
    ...

    D3jn\Larelastic\LarelasticServiceProvider::class,

    ...
],
```

Lastly you should publish it's configuration file:

```
php artisan vendor:publish --provider="D3jn\Larelastic\LarelasticServiceProvider"
```

Now you can proceed with configuring this package for your needs.

## Configuration

At first you will need to setup your hosts for Elasticsearch client accordingly in your `.env` file:

```
ELASTICSEARCH_DEFAULT_INDEX=test_index
ELASTICSEARCH_HOST=elasticsearch
ELASTICSEARCH_PORT=9200
ELASTICSEARCH_PROTOCOL=http
ELASTICSEARCH_USER=elastic
ELASTICSEARCH_PASSWORD=changeme
```

Open `config/larelastic.php`. At first you need to declare your types. Let's say we have `App\Post` model that
we want to use with Elasticsearch. Simply add it to `types` array:

```php
'types' => [
    App\Post::class,
],
```

> Configuration file is well documented on it's own, so feel free to add any additional setup using provided options.

For our `App\Post` model to be correctly recognized as valid type it needs to implement `D3jn\Larelastic\Contracts\Models\Searchable` interface. This package already provides you with a trait `D3jn\Larelastic\Models\Traits\Searchable` that includes default implementation of said interface for Eloquent models. Therefore we can just update our `App\Post` model as following:

```php
use D3jn\Larelastic\Contracts\Models\Searchable as SearchableContract;
use D3jn\Larelastic\Models\Traits\Searchable as SearchableTrait;

...

class Post extends Model implements SearchableContract
{
    use SearchableTrait;

...

}
```

> If you want to use non-eloquent objects with Elasticsearch then you will have to provide your own implementation of `D3jn\Larelastic\Contracts\Models\Searchable` interface.

If we want to define specific mapping for our type then our model must also contain `$typeMapping` field:

```php
/**
 * Mapping for comment type.
 *
 * @var array
 */
protected $typeMapping = [
    '_source' => [
        'enabled' => false
    ],
    'properties' => [
        'id' => ['type' => 'integer'],
        'content' => ['type' => 'text'],
        'title' => ['type' => 'text'],
        'created_at' => ['type' => 'date'],
    ]
];
```

With specific mapping you will also need to define `toSearchArray()` for your model. This method will be used to map your models properly:

```php
/**
 * Serialize current model instance into array for it's type.
 *
 * @return array
 */
protected function toSearchArray(): array
{
    return [
        'id' => $this->id,
        'content' => $this->content,
        'title' => $this->title,
        'created_at' => $this->created_at->timestamp,
    ];
}
```

> `D3jn\Larelastic\Models\Traits\Searchable` trait allows you to override a lot of it's behaviour. Feel free to look into it's code to find out about additional capabilities it provides.

Lastly we need to create our defined mappings and put our models into Elasticsearch. This package provides `larelastic:index` Artisan command for this case. Just run from the command line:

```
php artisan larelastic:index
```

In our case it will (re)create needed indices, types and put all our `posts` rows there.

## Basic usage

> Since we are using `D3jn\Larelastic\Models\Traits\Searchable` for our `App\Post` model we already have attached observer that will sync our changes (creating, updating or deleting) into Elasticsearch.

This package implements it's own query builder. You can access it by `Larelastic` facade:

```php
$posts = Larelastic::posts()
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

Here `posts` is the type of our `App\Post` model (for Eloquent implementation of `Searchable` trait it equals to table name by default, but you can override it in your model's class). It will return us an new query builder instance for `posts` type that has a lot of methods that mimick similar behaviour from Laravel`s very own query builder.

The most important part is methods like `get()` will return you hydrated collection of **Eloquent objects**! Each of them will have attached Elasticsearch data response too that you can access via `getElasticData()` method. Method `find()` return you one Eloquent object or null (if not found) and even `paginate()` method is implemented and works exactly the same!

You can construct your query using chain calling starting with `bool()` method first, for example:

```php
$query = Larelastic::posts()
    ->bool()
    ->filter()
    ->must(
        Larelastic::query()->range('created_at')->lte(time())
    );
```

Here we are creating and passing query `RangeQuery` object into `must` clause via `query()` helper method. But you can also import and construct supported objects manually:

```php
use D3jn\Larelastic\Query\Fulltext\MatchPhrasePrefixQuery;
use D3jn\Larelastic\Query\Fulltext\MatchPhraseQuery;
use D3jn\Larelastic\Query\Fulltext\MatchQuery;
use D3jn\Larelastic\Query\Fulltext\MultiMatchQuery;
use D3jn\Larelastic\Query\Term\RangeQuery;
use D3jn\Larelastic\Query\Term\TermQuery;
use D3jn\Larelastic\Query\Term\TermsQuery;

...

$query->bool()->should(
    new MultiMatchQuery(['title^5', 'content'], 'foo')
);
```

> Explore package classes to look for all supported features (such as highlighting, raw requests and so on). All classes and methods are well documented to provide easy understanding of their signatures and usage.

If options provided by builder interface are not enough then you can always use it's `requestRaw()` and `queryRaw()` to set arrays of raw data for your Elasticsearch requests and `raw()` method to get untouched array result from it.

## Built With

* [Laravel](http://laravel.com) - The web framework used
* [Elasticsearch PHP API](www.dropwizard.io/1.0.2/docs/) - Library for elasticsearch integration

## Authors

* **Serhii Yaniuk** - [d3jn](https://twitter.com/d3jn_)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* [Tony Messias](https://twitter.com/tony0x01) for part of the original concept that can be found [here](https://blog.madewithlove.be/post/how-to-integrate-your-laravel-app-with-elasticsearch/)
