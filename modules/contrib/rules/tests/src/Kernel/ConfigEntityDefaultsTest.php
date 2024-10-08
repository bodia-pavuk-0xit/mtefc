<?php

declare(strict_types=1);

namespace Drupal\Tests\rules\Kernel;

/**
 * Tests default config.
 *
 * @group Rules
 */
class ConfigEntityDefaultsTest extends RulesKernelTestBase {

  /**
   * The entity storage for Rules config entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rules',
    'rules_test',
    'rules_test_default_component',
    'user',
    'system',
  ];

  /**
   * Ensure strict config schema checking.
   *
   * @var bool
   */
  protected $strictConfigSchema = TRUE;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->storage = $this->entityTypeManager->getStorage('rules_component');
    $this->installConfig(['rules_test_default_component']);
  }

  /**
   * Tests Rules default components.
   */
  public function testDefaultComponents(): void {
    $config_entity = $this->storage->load('rules_test_default_component');

    $user = $this->entityTypeManager->getStorage('user')
      ->create(['mail' => 'test@example.com']);

    $result = $config_entity
      ->getComponent()
      ->setContextValue('user', $user)
      ->execute();

    // Test that the action was executed correctly.
    $messages = $this->container->get('messenger')->all();
    $message_string = isset($messages['status'][0]) ? (string) $messages['status'][0] : NULL;
    $this->assertEquals($message_string, 'test@example.com');

    $this->assertEquals('test@example.comtest@example.com', $result['concatenated']);
  }

}
