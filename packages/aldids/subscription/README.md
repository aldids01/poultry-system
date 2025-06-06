![Screenshot](https://raw.githubusercontent.com/aldids/subscription/master/art/screenshot.jpg)

# Subscription

[![Latest Stable Version](https://poser.pugx.org/aldids/subscription/version.svg)](https://packagist.org/packages/aldids/subscription)
[![License](https://poser.pugx.org/aldids/subscription/license.svg)](https://packagist.org/packages/aldids/subscription)
[![Downloads](https://poser.pugx.org/aldids/subscription/d/total.svg)](https://packagist.org/packages/aldids/subscription)

Laravel filament multitenant subscription plugin

## Installation

```bash
composer require aldids/subscription
```
after install your package please run this command

```bash
php artisan subscription:install
```



## Publish Assets

you can publish config file by use this command

```bash
php artisan vendor:publish --tag="subscription-config"
```

you can publish views file by use this command

```bash
php artisan vendor:publish --tag="subscription-views"
```

you can publish languages file by use this command

```bash
php artisan vendor:publish --tag="subscription-lang"
```

you can publish migrations file by use this command

```bash
php artisan vendor:publish --tag="subscription-migrations"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please see [SECURITY](SECURITY.md) for more information about security.

## Credits

- [Nchandom Alpha Elias](mailto:nchandoms@gmail.com)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
