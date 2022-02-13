# Laravel Version

[![Latest Version on Packagist](https://img.shields.io/packagist/v/axeldotdev/laravel-version.svg?style=flat-square)](https://packagist.org/packages/axeldotdev/laravel-version)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/axeldotdev/laravel-version/run-tests?label=tests)](https://github.com/axeldotdev/laravel-version/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/axeldotdev/laravel-version/Check%20&%20fix%20styling?label=code%20style)](https://github.com/axeldotdev/laravel-version/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/axeldotdev/laravel-version.svg?style=flat-square)](https://packagist.org/packages/axeldotdev/laravel-version)
[![License](https://img.shields.io/packagist/l/axeldotdev/laravel-version.svg?style=flat-square)](https://packagist.org/packages/axeldotdev/laravel-version)

Laravel Version allow you to easily manage your release versions including Git tags, changelog and showing the version in your frontend.

## Installation

You can install the package via composer:

```bash
composer require --dev axeldotdev/laravel-version
```

The minimum PHP version required is **8.1**.

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-version-config"
```

## Usage

You can run the command to change your application version:

```bash
php artisan app:version {version}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Axel Charpentier](https://github.com/axeldotdev)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
