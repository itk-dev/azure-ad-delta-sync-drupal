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

$config['azure_ad_delta_sync.settings']['azure']['client_id'] = '…';
$config['azure_ad_delta_sync.settings']['azure']['client_secret'] = '…';
$config['azure_ad_delta_sync.settings']['azure']['group_id'] = '…';
$config['azure_ad_delta_sync.settings']['azure']['tenant_id'] = '…';
```

Furthermore, you may want to install the [Config
Ignore](https://www.drupal.org/project/config_ignore) module and ignore the
`azure_ad_delta_sync.settings` config if committing config to a version control
system.

## Usage

A cron job should run the following command at regular intervals:

```sh
vendor/bin/drush azure_ad_delta_sync:run --force
```

Run `vendor/bin/drush azure_ad_delta_sync:run --help` for details on the command.

## Development

For development you need a full Drupal project. See
[itk-dev/azure-ad-delta-sync-drupal-test](https://github.com/itk-dev/azure-ad-delta-sync-drupal-test)
for an example.

We use a lazy services, `azure_ad_delta_sync.user_manager`
(`Drupal\azure_ad_delta_sync\UserManager`) and `azure_ad_delta_sync.controller`
(`Drupal\azure_ad_delta_sync\Controller`), , which requires generating a prozy
class (cf. <https://www.webomelette.com/lazy-loaded-services-drupal-8>).

Run the following commands to update the proxy classes:

```sh
php «DRUPAL_ROOT»/web/core/scripts/generate-proxy-class.php 'Drupal\azure_ad_delta_sync\UserManager' web/modules/contrib/azure_ad_delta_sync/src
php «DRUPAL_ROOT»/web/core/scripts/generate-proxy-class.php 'Drupal\azure_ad_delta_sync\Controller web/modules/contrib/azure_ad_delta_sync/src
```

## Automated tests

Requires a full Drupal installation with the `azure_ad_delta_sync` module in the
`web/modules/contrib` folder.

```sh
(cd «DRUPAL_ROOT»/web; ../vendor/bin/phpunit modules/contrib/azure_ad_delta_sync/tests/src/Functional)
```

### Coding standards

The code follows the [Drupal Coding
Standards](https://www.drupal.org/docs/develop/standards) (cf.
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

### Code analysis

[drupal-check](https://github.com/mglaman/drupal-check) is used to perform
static analysis of the code. Run

```sh
composer code-analysis
```

### GitHub Actions

We use [GitHub Actions](https://github.com/features/actions) to check coding
standards, perform code analysis and run automated tests whenever a pull request
is made (cf. [`.github/workflows/pr.yaml`](.github/workflows/pr.yaml)).

Before making a pull request you can run the GitHub Actions locally to check for
any problems:

[Install `act`](https://github.com/nektos/act#installation) and run

```sh
act -P ubuntu-latest=shivammathur/node:focal pull_request
```

(cf. <https://github.com/shivammathur/setup-php#local-testing-setup>).
