# Azure AD Delta Sync for Drupal

Drupal module for [Azure AD Delta Sync](https://github.com/itk-dev/azure-ad-delta-sync).

## Installation

```sh
composer require itk-dev/azure_ad_delta_sync
vendor/bin/drush pm:enable azure_ad_delta_sync
```

Go to `/admin/config/azure_ad_delta_sync` to set up the module.

You will probably want to add api keys in `settings.local.php`, i.e.

```php
# settings.local.php

$config['azure_ad_delta_sync.settings']['api']['client_id'] = '…';
$config['azure_ad_delta_sync.settings']['api']['client_secret'] = '…';
$config['azure_ad_delta_sync.settings']['api']['group_id'] = '…';
$config['azure_ad_delta_sync.settings']['api']['tenant_id'] = '…';
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

We use a lazy services, `azure_ad_delta_sync.user_manager`
(`Drupal\azure_ad_delta_sync\UserManager`) and `azure_ad_delta_sync.controller`
(`Drupal\azure_ad_delta_sync\Controller`), , which requires generating a prozy
class (cf. <https://www.webomelette.com/lazy-loaded-services-drupal-8>).

Run the following commands to update the proxy classes:

```sh
php «DRUPAL_ROOT»/web/core/scripts/generate-proxy-class.php 'Drupal\azure_ad_delta_sync\UserManager' web/modules/contrib/azure_ad_delta_sync/src
php «DRUPAL_ROOT»/web/core/scripts/generate-proxy-class.php 'Drupal\azure_ad_delta_sync\Controller web/modules/contrib/azure_ad_delta_sync/src
```

### Coding standards

```sh
composer install
composer coding-standards-check
```

```sh
composer coding-standards-apply
```
