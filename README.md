> ### This package will no longer be maintained. It's been used only in a few projects so far and is pretty simple at what it does for them, so no harm will come from archiving it.

# Larelastic
Yet another take on integrating Elasticsearch into Laravel.

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

## Built With
* [Laravel](http://laravel.com) - The web framework used
* [Elasticsearch PHP API](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html) - Library for elasticsearch PHP integration

## Authors
* **Serhii Yaniuk** - d3jn

## License
This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments
* [Tony Messias](https://twitter.com/tony0x01) for the original concept that can be found [here](https://blog.madewithlove.be/post/how-to-integrate-your-laravel-app-with-elasticsearch/)
