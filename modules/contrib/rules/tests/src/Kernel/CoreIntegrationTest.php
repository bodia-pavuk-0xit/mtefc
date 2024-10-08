<?php

declare(strict_types=1);

namespace Drupal\Tests\rules\Kernel;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\Entity\Node;
use Drupal\rules\Context\ContextConfig;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Engine\RulesComponent;
use Drupal\rules\Entity\RulesComponentConfig;
use Drupal\user\Entity\User;

// cspell:ignore klausi

/**
 * Test using Drupal core integration of Rules API.
 *
 * @group Rules
 */
class CoreIntegrationTest extends RulesKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field', 'text', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
  }

  /**
   * Tests that a complex data selector can be applied to entities.
   */
  public function testEntityPropertyPath(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('node_type')
      ->create(['type' => 'page'])
      ->save();

    $node = $entity_type_manager->getStorage('node')
      ->create([
        'title' => 'test',
        'type' => 'page',
      ]);

    $user = $entity_type_manager->getStorage('user')
      ->create([
        'name' => 'test value',
      ]);

    $user->save();
    $node->setOwner($user);

    $rule = $this->expressionManager->createRule();

    // Test that the long detailed data selector works.
    $rule->addCondition('rules_test_string_condition', ContextConfig::create()
      ->map('text', 'node.uid.0.entity.name.0.value')
    );

    // Test that the shortened data selector without list indices.
    $rule->addCondition('rules_test_string_condition', ContextConfig::create()
      ->map('text', 'node.uid.entity.name.value')
    );

    $rule->addAction('rules_test_debug_log');

    RulesComponent::create($rule)
      ->addContextDefinition('node', ContextDefinition::create('entity:node'))
      ->setContextValue('node', $node)
      ->execute();

    // Test that the action logged something.
    $this->assertRulesDebugLogEntryExists('action called');
  }

  /**
   * Tests that an entity is automatically saved after being changed.
   */
  public function testEntityAutoSave(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('node_type')
      ->create(['type' => 'page'])
      ->save();

    $node = $entity_type_manager->getStorage('node')
      ->create([
        'title' => 'test',
        'type' => 'page',
      ]);

    // We use the rules_test_node action plugin which marks its node context for
    // auto saving.
    // @see \Drupal\rules_test\Plugin\RulesAction\TestNodeAction
    $action = $this->expressionManager->createAction('rules_test_node')
      ->setConfiguration(ContextConfig::create()
        ->map('node', 'node')
        ->map('title', 'title')
        ->toArray()
      );

    RulesComponent::create($action)
      ->addContextDefinition('node', ContextDefinition::create('entity:node'))
      ->addContextDefinition('title', ContextDefinition::create('string'))
      ->setContextValue('node', $node)
      ->setContextValue('title', 'new title')
      ->execute();
    $this->assertNotNull($node->id(), 'Node ID is set, which means that the node has been saved.');
  }

  /**
   * Tests that tokens in action parameters get replaced.
   */
  public function testTokenReplacements(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('node_type')
      ->create(['type' => 'page'])
      ->save();

    $node = $entity_type_manager->getStorage('node')
      ->create([
        'title' => 'test',
        'type' => 'page',
      ]);

    $user = $entity_type_manager->getStorage('user')
      ->create([
        'name' => 'klausi',
      ]);

    $user->save();
    $node->setOwner($user);

    // Configure a simple rule with one action.
    $action = $this->expressionManager->createInstance('rules_action',
      ContextConfig::create()
        ->map('message', 'message')
        ->map('type', 'type')
        ->process('message', 'rules_tokens')
        ->setConfigKey('action_id', 'rules_system_message')
        ->toArray()
    );

    $rule = $this->expressionManager->createRule()
      ->addExpressionObject($action);

    RulesComponent::create($rule)
      ->addContextDefinition('node', ContextDefinition::create('entity:node'))
      ->addContextDefinition('message', ContextDefinition::create('string'))
      ->addContextDefinition('type', ContextDefinition::create('string'))
      ->setContextValue('node', $node)
      ->setContextValue('message', 'Hello {{ node.uid.entity.name.value }}!')
      ->setContextValue('type', 'status')
      ->execute();

    $messages = $this->messenger->all();
    $this->assertEquals((string) $messages[MessengerInterface::TYPE_STATUS][0], 'Hello klausi!');
  }

  /**
   * Tests that tokens used to format entity fields get replaced.
   */
  public function testTokenFormattingReplacements(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('node_type')
      ->create(['type' => 'page'])
      ->save();

    $node = $entity_type_manager->getStorage('node')
      ->create([
        'title' => 'test',
        'type' => 'page',
        // Set the created date to the first second in 1970.
        'created' => 1,
      ]);

    // Configure a simple rule with one action.
    $action = $this->expressionManager->createInstance('rules_action',
      ContextConfig::create()
        ->map('message', 'message')
        ->map('type', 'type')
        ->process('message', 'rules_tokens')
        ->setConfigKey('action_id', 'rules_system_message')
        ->toArray()
    );

    $rule = $this->expressionManager->createRule()
      ->addExpressionObject($action);

    RulesComponent::create($rule)
      ->addContextDefinition('node', ContextDefinition::create('entity:node'))
      ->addContextDefinition('message', ContextDefinition::create('string'))
      ->addContextDefinition('type', ContextDefinition::create('string'))
      ->setContextValue('node', $node)
      ->setContextValue('message', "The node was created in the year {{ node.created.value | format_date('custom', 'Y') }}")
      ->setContextValue('type', 'status')
      ->execute();

    $messages = $this->messenger->all();
    $this->assertEquals((string) $messages[MessengerInterface::TYPE_STATUS][0], 'The node was created in the year 1970');
  }

  /**
   * Tests that the data set action works on entities.
   */
  public function testDataSetEntities(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('node_type')
      ->create(['type' => 'page'])
      ->save();

    $node = $entity_type_manager->getStorage('node')
      ->create([
        'title' => 'test',
        'type' => 'page',
      ]);

    // Configure a simple rule with one action.
    $action = $this->expressionManager->createInstance('rules_action',
      ContextConfig::create()
        ->setConfigKey('action_id', 'rules_data_set')
        ->map('data', 'node.title')
        ->map('value', 'new_title')
        ->toArray()
    );

    $rule = $this->expressionManager->createRule()
      ->addExpressionObject($action);

    RulesComponent::create($rule)
      ->addContextDefinition('node', ContextDefinition::create('entity:node'))
      ->addContextDefinition('new_title', ContextDefinition::create('string'))
      ->setContextValue('node', $node)
      ->setContextValue('new_title', 'new title')
      ->execute();

    $this->assertEquals('new title', $node->getTitle());
    $this->assertNotNull($node->id(), 'Node ID is set, which means that the node has been auto-saved.');
  }

  /**
   * Tests that auto saving in a component executed as action works.
   */
  public function testComponentActionAutoSave(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('node_type')
      ->create(['type' => 'page'])
      ->save();

    $nested_rule = $this->expressionManager->createRule();
    // Create a node entity with the action.
    $nested_rule->addAction('rules_entity_create:node', ContextConfig::create()
      ->setValue('type', 'page')
    );
    // Set the title of the new node so that it is marked for auto-saving.
    $nested_rule->addAction('rules_data_set', ContextConfig::create()
      ->map('data', 'node_created.title')
      ->setValue('value', 'new title')
    );

    $rules_config = new RulesComponentConfig([
      'id' => 'test_rule',
      'label' => 'Test rule',
    ], 'rules_component');
    $rules_config->setExpression($nested_rule);
    $rules_config->save();

    // Invoke the rules component in another rule.
    $rule = $this->expressionManager->createRule();
    $rule->addAction('rules_component:test_rule');

    RulesComponent::create($rule)->execute();

    $nodes = Node::loadMultiple();
    $node = reset($nodes);
    $this->assertEquals('new title', $node->getTitle());
    $this->assertNotNull($node->id(), 'Node ID is set, which means that the node has been auto-saved.');
  }

  /**
   * Tests using global context.
   */
  public function testGlobalContext(): void {
    $account = User::create([
      'name' => 'hubert',
    ]);
    $account->save();
    $this->container->get('current_user')->setAccount($account);

    $rule = $this->expressionManager->createRule()
      ->addAction('rules_system_message', ContextConfig::create()
        ->map('message', '@user.current_user_context:current_user.name.value')
        ->setValue('type', 'status')
      );
    $component = RulesComponent::create($rule);
    $this->assertEquals(0, $component->checkIntegrity()->count());

    // Ensure the execution-state is aware of global context.
    $result = $component->getState()
      ->hasVariable('@user.current_user_context:current_user');
    $this->assertTrue($result);
    // Test asking for non-existing variables.
    $this->assertFalse($component->getState()
      ->hasVariable('@user.current_user_context:invalid'));
    $this->assertFalse($component->getState()
      ->hasVariable('@user.invalid_service'));
    $this->assertFalse($component->getState()
      ->hasVariable('invalid-var'));

    // Test using global context during execution.
    $component->execute();
    $messages = $this->messenger->all();
    $this->assertEquals((string) $messages[MessengerInterface::TYPE_STATUS][0], 'hubert');
  }

}
