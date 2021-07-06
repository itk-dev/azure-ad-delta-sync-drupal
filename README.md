# adgangsstyring-drupal

Drupal module for Adgangsstyring

## Installation

```sh
composer require itkdev/adgangsstyring_drupal
vendor/bin/drush pm:enable adgangsstyring
```

Go to `/admin/config/adgangsstyring` to set up the module.

## Usage

A cron job should run the following command at regular intervals:

```sh
vendor/bin/drush adgangsstyring:run --help
```

Run `vendor/bin/drush adgangsstyring:run --help` for details on the command.

## Coding standards

```sh
composer install
composer coding-standards-check
```

```sh
composer coding-standards-apply
```
