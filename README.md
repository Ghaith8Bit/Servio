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

### Publish Configuration

Publish the configuration file using the following command:

```bash
php artisan vendor:publish --provider="Mrclutch\Servio\Providers\ServioServiceProvider"
```

This command will create a `servio.php` configuration file in your `config` directory.

### Configuration File

The `servio.php` configuration file allows you to define the structure and attributes of your services. Here’s an example of how to configure your services:

```php
<?php

return [
    'User' => [
        'table' => 'users',
        'dto' => [
            'fields' => [
                'name' => 'required|string',
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ],
        ],
        'resource' => [
            'fields' => [
                'id',
                'name',
                'email',
            ],
        ],
    ],
];
```

### Configuration Details

- **Service Name**: The array key represents the name of the service. This name will be used when generating service components.
  
- **Table Name**: The `table` attribute specifies the database table associated with the service.

- **DTO Attributes**: Under the `dto` key, you define the fields for the Data Transfer Object (DTO). Each field should have its validation rules specified.

- **Resource Fields**: Under the `resource` key, list the fields to be included in the resource transformation.

### Generating Service Components

You can now use the `make:servio` command to generate service components:

```bash
php artisan make:service YourServiceName
```

Replace `YourServiceName` with the desired name of your service. This command will generate various components for the service based on your configuration.

## License

This package is licensed under the MIT License.
