<?php

namespace Drupal\rules\Plugin\Condition;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\Type\BooleanInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Core\Attribute\Condition;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Data value is empty' condition.
 *
 * @todo Add access callback information from Drupal 7.
 *
 * @Condition(
 *   id = "rules_data_is_empty",
 *   label = @Translation("Data value is empty"),
 *   category = @Translation("Data"),
 *   context_definitions = {
 *     "data" = @ContextDefinition("any",
 *       label = @Translation("Data to check"),
 *       description = @Translation("The data to be checked to be empty, specified by using a data selector, e.g. 'node.uid.entity.name.value'."),
 *       assignment_restriction = "selector"
 *     ),
 *   }
 * )
 */
#[Condition(
  id: "rules_data_is_empty",
  label: new TranslatableMarkup("Data value is empty"),
  category: new TranslatableMarkup("Data"),
  context_definitions: [
    "data" => new ContextDefinition(
      data_type: "any",
      label: new TranslatableMarkup("Data to check"),
      description: new TranslatableMarkup("The data to be checked to be empty, specified by using a data selector, e.g. 'node.uid.entity.name.value'."),
      assignment_restriction: "selector"
    ),
  ]
)]
class DataIsEmpty extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $data = $this->getContext('data')->getContextData();
    if ($data instanceof ComplexDataInterface || $data instanceof ListInterface) {
      return $data->isEmpty();
    }
    $value = $data->getValue();
    // For some primitives we can rely on PHP's type casting to boolean.
    if ($data instanceof StringInterface || $data instanceof IntegerInterface || $data instanceof BooleanInterface) {
      return !isset($value) || !$value;
    }
    return !isset($value);
  }

}
