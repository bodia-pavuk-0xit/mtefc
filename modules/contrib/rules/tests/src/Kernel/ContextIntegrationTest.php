<?php

declare(strict_types=1);

namespace Drupal\Tests\rules\Kernel;

use Drupal\rules\Context\ContextConfig;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Context\ContextDefinitionInterface;
use Drupal\rules\Engine\RulesComponent;
use Drupal\rules\Exception\EvaluationException;

/**
 * Tests the extended core context API with Rules.
 *
 * @group Rules
 */
class ContextIntegrationTest extends RulesKernelTestBase {

  /**
   * Tests that a required context mapping that is NULL throws an exception.
   */
  public function testRequiredNullMapping(): void {
    // Configure a simple rule with one action.
    $action = $this->expressionManager->createInstance('rules_action',
      ContextConfig::create()
        ->setConfigKey('action_id', 'rules_test_string')
        ->map('text', 'null_context')
        ->toArray()
    );

    $rule = $this->expressionManager->createRule()
      ->addExpressionObject($action);

    $component = RulesComponent::create($rule)
      ->addContextDefinition('null_context', ContextDefinition::create('string'))
      ->setContextValue('null_context', NULL);

    try {
      $component->execute();
      $this->fail('No exception thrown when required context value is NULL');
    }
    catch (EvaluationException $e) {
      $this->assertTrue(TRUE, 'Exception thrown as expected when a required context is NULL');
    }
  }

  /**
   * Tests that a required context value that is NULL throws an exception.
   */
  public function testRequiredNullValue(): void {
    // Configure a simple rule with one action. The required 'text' context is
    // set to be NULL.
    $action = $this->expressionManager->createInstance('rules_action',
      ContextConfig::create()
        ->setConfigKey('action_id', 'rules_test_string')
        ->setValue('text', NULL)
        ->toArray()
    );

    $rule = $this->expressionManager->createRule();
    $rule->addExpressionObject($action);
    try {
      $rule->execute();
      $this->fail('No exception thrown when required context value is NULL');
    }
    catch (EvaluationException $e) {
      $this->assertTrue(TRUE, 'Exception thrown as expected when a required context is NULL');
    }
  }

  /**
   * Tests that NULL values for contexts are allowed if specified.
   */
  public function testAllowNullValue(): void {
    // Configure a simple rule with the data set action which allows NULL
    // values.
    $action = $this->expressionManager->createInstance('rules_action',
      ContextConfig::create()
        ->setConfigKey('action_id', 'rules_data_set')
        ->map('data', 'null_variable')
        ->map('value', 'new_value')
        ->toArray()
    );

    $rule = $this->expressionManager->createRule()
      ->addExpressionObject($action);

    $component = RulesComponent::create($rule)
      ->addContextDefinition('null_variable', ContextDefinition::create('string'))
      ->addContextDefinition('new_value', ContextDefinition::create('string'))
      ->setContextValue('null_variable', NULL)
      ->setContextValue('new_value', 'new value');

    $component->execute();

    $this->assertEquals('new value', $component->getState()->getVariableValue('null_variable'));
  }

  /**
   * Tests the assignment restriction on context definitions.
   */
  public function testAssignmentRestriction(): void {
    $action_manager = $this->container->get('plugin.manager.rules_action');

    // Test the assignment restriction on the entity fetch action as an example.
    $entity_fetch_action = $action_manager->createInstance('rules_entity_fetch_by_id');
    $context_definition = $entity_fetch_action->getContextDefinition('type');
    $this->assertEquals(ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_INPUT, $context_definition->getAssignmentRestriction());

    // Test the assignment restriction on the entity delete action.
    $entity_delete_action = $action_manager->createInstance('rules_entity_delete');
    $context_definition = $entity_delete_action->getContextDefinition('entity');
    $this->assertEquals(ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR, $context_definition->getAssignmentRestriction());
  }

}
