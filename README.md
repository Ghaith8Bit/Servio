# Servio

Servio is a Laravel package designed to streamline and simplify the development of APIs within your Laravel application using SOA (Service-Oriented Architecture) principles.

## Installation

You can install the package via Composer:

```bash
composer require mrclutch/servio
```

## Usage

After installing the package, add the service provider to your `config/app.php` file:

```php
'providers' => [
    // Other service providers...
    MrClutch\Servio\ServioServiceProvider::class,
],
```

You can now use the `make:service` command to generate service components:

```bash
php artisan make:service YourServiceName
```

## License

This package is licensed under the MIT License.
