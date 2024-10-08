<?php

namespace Drupal\rules_test\Plugin\Condition;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Core\Attribute\Condition;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Test condition that adds a variable with the provided context.
 *
 * @Condition(
 *   id = "rules_test_provider",
 *   label = @Translation("Test condition provider"),
 *   category = @Translation("Tests"),
 *   provides = {
 *     "provided_text" = @ContextDefinition("string",
 *       label = @Translation("Provided text")
 *     ),
 *   }
 * )
 */
#[Condition(
  id: "rules_test_provider",
  label: new TranslatableMarkup("Test condition provider"),
  category: new TranslatableMarkup("Tests"),
  provides: [
    "provided_text" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("Provided text")
    ),
  ]
)]
class TestConditionProvider extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $this->setProvidedValue('provided_text', 'test value');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // We don't care about summaries for test condition plugins.
    return '';
  }

}
