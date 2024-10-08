<?php

namespace Drupal\rules\Plugin\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Core\Attribute\Condition;
use Drupal\rules\Core\RulesConditionBase;
use Drupal\rules\TypedData\Options\EntityTypeOptions;

/**
 * Provides an 'Entity is of type' condition.
 *
 * @todo Add access callback information from Drupal 7?
 *
 * @Condition(
 *   id = "rules_entity_is_of_type",
 *   label = @Translation("Entity is of type"),
 *   category = @Translation("Entity"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity for which to evaluate the condition."),
 *       assignment_restriction = "selector"
 *     ),
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Type"),
 *       description = @Translation("The entity type specified by the condition."),
 *       options_provider = "\Drupal\rules\TypedData\Options\EntityTypeOptions",
 *       assignment_restriction = "input"
 *     ),
 *   }
 * )
 */
#[Condition(
  id: "rules_entity_is_of_type",
  label: new TranslatableMarkup("Entity is of type"),
  category: new TranslatableMarkup("Entity"),
  context_definitions: [
    "entity" => new ContextDefinition(
      data_type: "entity",
      label: new TranslatableMarkup("Entity"),
      description: new TranslatableMarkup("Specifies the entity for which to evaluate the condition."),
      assignment_restriction: "selector"
    ),
    "type" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("Type"),
      description: new TranslatableMarkup("The entity type specified by the condition."),
      options_provider: EntityTypeOptions::class,
      assignment_restriction: "input"
    ),
  ]
)]
class EntityIsOfType extends RulesConditionBase {

  /**
   * Check if the provided entity is of a specific type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check for a type.
   * @param string $type
   *   The type to check for.
   *
   * @return bool
   *   TRUE if the entity is of the provided type.
   */
  protected function doEvaluate(EntityInterface $entity, $type) {
    $entity_type = $entity->getEntityTypeId();

    // Check to see whether the entity's type matches the specified value.
    return $entity_type == $type;
  }

}
