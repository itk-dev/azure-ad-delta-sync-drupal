# Azure AD Delta Sync for Drupal

Drupal module for [Azure AD Delta Sync](https://github.com/itk-dev/azure-ad-delta-sync).

## Installation

```sh
composer require itk-dev/azure_ad_delta_sync
vendor/bin/drush pm:enable azure_ad_delta_sync
```

Go to `/admin/config/azure_ad_delta_sync` to set up the module.

You will probably want to add Azure api keys in `settings.local.php`, i.e.

```php
# settings.local.php

$config['azure_ad_delta_sync.settings']['azure']['uri'] = '…';
$config['azure_ad_delta_sync.settings']['azure']['security_key'] = '…';
$config['azure_ad_delta_sync.settings']['azure']['client_secret'] = '…';
```

Furthermore, you may want to install the [Config Ignore](https://www.drupal.org/project/config_ignore) module and ignore
the `azure_ad_delta_sync.settings` config if committing config to a version control system.

## Usage

A cron job should run the following command at regular intervals:

```sh
vendor/bin/drush azure_ad_delta_sync:run --force
```

Run `vendor/bin/drush azure_ad_delta_sync:run --help` for details on the command.

## Development

For development you need a full Drupal project. See
[itk-dev/azure-ad-delta-sync-drupal-test](https://github.com/itk-dev/azure-ad-delta-sync-drupal-test) for an example.

We use lazy services, `aDrupal\azure_ad_delta_sync\UserManager` and `Drupal\azure_ad_delta_sync\Controller`, which
require generating proxy classes (cf. <https://www.webomelette.com/lazy-loaded-services-drupal-8>).

Run the following command to update the proxy classes:

```sh
./scripts/generate-proxy-classes
```

## Automated tests

Requires a full Drupal installation with the `azure_ad_delta_sync_drupal` module in the `web/modules/contrib` folder.

```sh
(cd «DRUPAL_ROOT»/web; ./vendor/bin/phpunit modules/contrib/azure_ad_delta_sync_drupal/tests/src/Functional)
```

### Coding standards

The code follows the [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards) (cf.
[`phpcs.xml.dist`](phpcs.xml.dist)) and can be checked by running

```sh
composer install
composer coding-standards-check
```

Use

```sh
composer coding-standards-apply
```

to automatically fix some coding standard violations.

#### Markdown files

```shell
docker run --rm --volume "$PWD:/md" peterdavehello/markdownlint markdownlint '**/*.md' --fix
docker run --rm --volume "$PWD:/md" peterdavehello/markdownlint markdownlint '**/*.md'
```

### Code analysis

phpstan is used to perform static analysis of the code. Run the following script:

```sh
./scripts/code-analysis
```

### Rector

Automatic code upgrades

`./scripts/rector`

### GitHub Actions

We use [GitHub Actions](https://github.com/features/actions) to check coding standards, perform code analysis and run
automated tests whenever a pull request is made (cf. [`.github/workflows/pr.yaml`](.github/workflows/pr.yaml)).

Before making a pull request you can run the GitHub Actions locally to check for any problems:

[Install `act`](https://github.com/nektos/act#installation) and run

```sh
act -P ubuntu-latest=shivammathur/node:focal pull_request
```

(cf. <https://github.com/shivammathur/setup-php#local-testing-setup>).
