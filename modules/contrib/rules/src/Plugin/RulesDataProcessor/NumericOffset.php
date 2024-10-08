<?php

namespace Drupal\rules\Plugin\RulesDataProcessor;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rules\Context\DataProcessorInterface;
use Drupal\rules\Context\ExecutionStateInterface;
use Drupal\rules\Attribute\RulesDataProcessor;

/**
 * A data processor for applying numerical offsets.
 *
 * The plugin configuration must contain the following entry:
 * - offset: the value that should be added.
 *
 * @RulesDataProcessor(
 *   id = "rules_numeric_offset",
 *   label = @Translation("Apply numeric offset")
 * )
 */
#[RulesDataProcessor(
  id: "rules_numeric_offset",
  label: new TranslatableMarkup("Apply numeric offset")
)]
class NumericOffset extends PluginBase implements DataProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process($value, ExecutionStateInterface $rules_state) {
    return $value + $this->configuration['offset'];
  }

}
