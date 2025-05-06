# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

* [PR-20](https://github.com/itk-dev/azure-ad-delta-sync-drupal/pull/20)
  Updated and added workflows
* [PR-18](https://github.com/itk-dev/azure-ad-delta-sync-drupal/pull/18)
  * Update `container->get` after autowire change
  * Change logging: <https://drupalize.me/blog/how-log-messages-drupal-8>
  * Remove unused `drupal_psr6_cache`
  * Add run-command script to test if command runs

## [2.0.1] - 2025-03-04

* [PR-16](https://github.com/itk-dev/azure-ad-delta-sync-drupal/pull/16)
  move requires to require dev
* [PR-15](https://github.com/itk-dev/azure-ad-delta-sync-drupal/pull/15)
  update setConfiguration to actually work
* [PR-14](https://github.com/itk-dev/azure-ad-delta-sync-drupal/pull/14)
  Added Rector and refactored and cleaned up code
* [PR-13](https://github.com/itk-dev/azure-ad-delta-sync-drupal/pull/13)
  Add dependencies openid_connect:openid_connect
* [PR-12](https://github.com/itk-dev/azure-ad-delta-sync-drupal/pull/12)
  Autowire services

## [2.0.0] - 2025-02-03

* Upgrade Drupal version
* Add changelog check to pr.yaml
* Change code analysis
* Update php version
* Update Drush
* Rewrite drush delta sync command a bit, because Drush was updated

## [1.0.0] 09-06-2021

* Initial release

[Unreleased]: https://github.com/itk-dev/azure-ad-delta-sync-drupal/compare/2.0.1...HEAD
[2.0.1]: https://github.com/itk-dev/azure-ad-delta-sync-drupal/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/itk-dev/azure-ad-delta-sync-drupal/compare/1.0.0...2.0.0
[1.0.0]: https://github.com/itk-dev/azure-ad-delta-sync-drupal/releases/tag/1.0.0
