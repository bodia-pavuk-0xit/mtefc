<?php

declare(strict_types=1);

namespace Drupal\Tests\rules\Kernel;

use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Engine\RulesComponent;
use Drupal\rules\Plugin\RulesExpression\RuleExpression;

/**
 * Tests storage and loading of Rules config entities.
 *
 * @group Rules
 */
class ConfigEntityTest extends RulesKernelTestBase {

  /**
   * The entity storage for Rules config entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->storage = $this->container->get('entity_type.manager')->getStorage('rules_component');
  }

  /**
   * Tests that an empty rule configuration can be saved.
   */
  public function testSavingEmptyRule(): void {
    // This test does not perform assertions, and the @doesNotPerformAssertions
    // annotation does not work properly in DrupalCI for PHP 7.4.
    // @see https://www.drupal.org/project/rules/issues/3179763
    $this->addToAssertionCount(1);

    $rule = $this->expressionManager->createRule();
    $config_entity = $this->storage->create([
      'id' => 'test_rule',
    ])->setExpression($rule);
    $config_entity->save();
  }

  /**
   * Tests saving the configuration of an action and then loading it again.
   */
  public function testConfigAction(): void {
    $action = $this->expressionManager->createAction('rules_test_debug_log');
    $config_entity = $this->storage->create([
      'id' => 'test_rule',
    ])->setExpression($action);
    $config_entity->save();

    // The logger instance has changed, refresh it.
    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->logger->addLogger($this->debugLog);

    $loaded_entity = $this->storage->load('test_rule');
    $this->assertEquals($action->getConfiguration(), $loaded_entity->get('component')['expression'], 'Action configuration is the same after loading the config.');

    // Create the Rules expression object from the configuration.
    $expression = $loaded_entity->getExpression();
    $expression->execute();

    // Test that the action logged something.
    $this->assertRulesDebugLogEntryExists('action called');
  }

  /**
   * Tests saving the nested config of a rule and then loading it again.
   */
  public function testConfigRule(): void {
    // Create a simple rule with one action and one condition.
    $rule = $this->expressionManager->createRule();
    $rule->addCondition('rules_test_true');
    $rule->addAction('rules_test_debug_log');

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
    ])->setExpression($rule);
    $config_entity->save();

    $loaded_entity = $this->storage->load('test_rule');
    // Create the Rules expression object from the configuration.
    $expression = $loaded_entity->getExpression();
    $expression->execute();

    // Test that the action logged something.
    $this->assertRulesDebugLogEntryExists('action called');
  }

  /**
   * Make sure that expressions using context definitions can be exported.
   */
  public function testContextDefinitionExport(): void {
    $component = RulesComponent::create($this->expressionManager->createRule())
      ->addContextDefinition('test', ContextDefinition::create('string')
        ->setLabel('Test string')
      );

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
    ])->updateFromComponent($component);
    $config_entity->save();

    $loaded_entity = $this->storage->load('test_rule');
    // Create the Rules expression object from the configuration.
    $expression = $loaded_entity->getExpression();
    $this->assertInstanceOf(RuleExpression::class, $expression);
    $context_definitions = $loaded_entity->getContextDefinitions();
    $this->assertEquals($context_definitions['test']->getDataType(), 'string', 'Data type of context definition is correct.');
    $this->assertEquals($context_definitions['test']->getLabel(), 'Test string', 'Label of context definition is correct.');
  }

  /**
   * Tests that a reaction rule config entity can be saved.
   */
  public function testReactionRuleSaving(): void {
    // This test does not perform assertions, and the @doesNotPerformAssertions
    // annotation does not work properly in DrupalCI for PHP 7.4.
    // @see https://www.drupal.org/project/rules/issues/3179763
    $this->addToAssertionCount(1);

    $rule = $this->expressionManager->createRule();
    $storage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');
    $config_entity = $storage->create([
      'id' => 'test_rule',
    ])->setExpression($rule);
    $config_entity->save();
  }

}
