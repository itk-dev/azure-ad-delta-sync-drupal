<?php

namespace Drupal\Tests\azure_ad_delta_sync\Functional;

use Drupal\azure_ad_delta_sync\Helpers\ConfigHelper;
use Drupal\azure_ad_delta_sync\UserManager;
use Drupal\azure_ad_delta_sync\UserManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;

/**
 * Tests the user manager.
 *
 * @group azure_ad_delta_sync
 */
class UserManagerTest extends BrowserTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = ['azure_ad_delta_sync'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();
    $this->createRoles();
    $this->createUsers();
  }

  /**
   * Test load managed user ids.
   *
   * @dataProvider loadManagedUserIdsProvider
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testLoadManagedUserIds(array $moduleConfig, int $expected) {
    $userManager = $this->getUserManager($moduleConfig);
    $userIds = $userManager->loadManagedUserIds();
    $this->assertCount($expected, $userIds);
  }

  /**
   * Data provider.
   *
   * @return array[]
   *   List of [module config, expected value].
   */
  public static function loadManagedUserIdsProvider() {
    return [

      [
        [
          'azure.user_id_claim' => 'userPrincipalName',
          'drupal.user_id_field' => 'name',
          'include' => [
            'modules' => [],
          ],
          'exclude' => [
            'roles' => [],
            'users' => [],
          ],
        ],
        98,
      ],

      [
        [
          'azure.user_id_claim' => 'userPrincipalName',
          'drupal.user_id_field' => 'name',
          'include' => [
            'modules' => [],
          ],
          'exclude' => [
            'roles' => [
              'role1' => 'role1',
              'users' => [],
            ],
          ],
        ],
        74,
      ],

      [
        [
          'azure.user_id_claim' => 'userPrincipalName',
          'drupal.user_id_field' => 'name',
          'include' => [
            'modules' => [],
          ],
          'exclude' => [
            'roles' => [
              'role1' => [],
            ],
            'users' => [
              '87',
            ],
          ],
        ],
        97,
      ],

    ];
  }

  /**
   * Get user manager instance.
   *
   * @param array $moduleConfig
   *   The module config.
   *
   * @return \Drupal\azure_ad_delta_sync\UserManagerInterface
   *   The user manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getUserManager(array $moduleConfig): UserManagerInterface {
    $config = $this->createMock(ImmutableConfig::class);
    $config
      ->expects($this->any())
      ->method('get')
      ->willReturnCallback(function (string $key) use ($moduleConfig) {
        if (array_key_exists($key, $moduleConfig)) {
          return $moduleConfig[$key];
        }
        throw new \InvalidArgumentException($key);
      });

    $configFactory = $this->createMock(ConfigFactoryInterface ::class);
    $configFactory
      ->expects($this->once())
      ->method('get')
      ->willReturn($config);
    $logger = $this->createMock(LoggerInterface::class);

    return new UserManager(
      $this->container->get('entity_type.manager'),
      $this->container->get('database'),
      $this->container->get('request_stack'),
      $logger,
      $this->container->get(ConfigHelper::class),
    );
  }

  /**
   * Create user roles.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createRoles() {
    $roles = Role::loadMultiple();
    // Roles anonymous and authenticated always exist.
    $this->assertCount(2, $roles);

    for ($i = 0; $i < 4; $i++) {
      $role = Role::create([
        'id' => sprintf('role%d', $i),
        'label' => sprintf('Role %d', $i),
      ]);
      $role->save();
    }

    $roles = Role::loadMultiple();
    // Roles anonymous and authenticated always exist.
    $this->assertCount(2 + 4, $roles);
  }

  /**
   * Create users with roles.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createUsers() {
    $users = User::loadMultiple();
    // Users 0 and 1 always exist.
    $this->assertCount(2, $users);

    $roles = Role::loadMultiple();

    for ($id = 2; $id < 100; $id++) {
      $name = sprintf('user%d', $id);
      $user = User::create([
        'uid' => $id,
        'name' => $name,
        'mail' => sprintf('%s@example.com', $name),
      ]);
      $user->activate();
      $user->addRole($roles['role' . ($id % 4)]);
      $user->save();
    }

    $users = User::loadMultiple();
    // Roles anonymous and authenticated always exist.
    $this->assertCount(100, $users);
  }

}
