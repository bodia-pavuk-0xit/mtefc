<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Core\Attribute\RulesAction;
use Drupal\rules\Core\RulesActionBase;
use Drupal\rules\TypedData\Options\CalculationOperatorOptions;

/**
 * Provides a 'numeric calculation' action.
 *
 * @todo Add access callback information from Drupal 7.
 * @todo Add defined operation options from Drupal 7.
 * @todo If context args are integers, ensure that integers are returned.
 *
 * @RulesAction(
 *   id = "rules_data_calculate_value",
 *   label = @Translation("Calculate a numeric value"),
 *   category = @Translation("Data"),
 *   context_definitions = {
 *     "input_1" = @ContextDefinition("float",
 *       label = @Translation("Input value 1"),
 *       description = @Translation("The first input value for the calculation.")
 *     ),
 *     "operator" = @ContextDefinition("string",
 *       label = @Translation("Operator"),
 *       description = @Translation("The calculation operator."),
 *       options_provider = "\Drupal\rules\TypedData\Options\CalculationOperatorOptions",
 *       assignment_restriction = "input"
 *     ),
 *     "input_2" = @ContextDefinition("float",
 *       label = @Translation("Input value 2"),
 *       description = @Translation("The second input value for the calculation.")
 *     ),
 *   },
 *   provides = {
 *     "result" = @ContextDefinition("float",
 *       label = @Translation("Calculated result")
 *     ),
 *   }
 * )
 */
#[RulesAction(
  id: "rules_data_calculate_value",
  label: new TranslatableMarkup("Calculate a numeric value"),
  category: new TranslatableMarkup("Data"),
  context_definitions: [
    "input_1" => new ContextDefinition(
      data_type: "float",
      label: new TranslatableMarkup("Input value 1"),
      description: new TranslatableMarkup("The first input value for the calculation.")
    ),
    "operator" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("Operator"),
      description: new TranslatableMarkup("The calculation operator."),
      options_provider: CalculationOperatorOptions::class,
      assignment_restriction: "input"
    ),
    "input_2" => new ContextDefinition(
      data_type: "float",
      label: new TranslatableMarkup("Input value 2"),
      description: new TranslatableMarkup("The second input value for the calculation.")
    ),
  ],
  provides: [
    "result" => new ContextDefinition(
      data_type: "float",
      label: new TranslatableMarkup("Calculated result")
    ),
  ]
)]
class DataCalculateValue extends RulesActionBase {

  /**
   * Executes the action with the given context.
   *
   * @param float $input_1
   *   The first input value.
   * @param string $operator
   *   The operator that should be applied.
   * @param float $input_2
   *   The second input value.
   */
  protected function doExecute($input_1, $operator, $input_2) {
    switch ($operator) {
      case '+':
        $result = $input_1 + $input_2;
        break;

      case '-':
        $result = $input_1 - $input_2;
        break;

      case '*':
        $result = $input_1 * $input_2;
        break;

      case '/':
        $result = $input_1 / $input_2;
        break;

      case 'min':
        $result = min($input_1, $input_2);
        break;

      case 'max':
        $result = max($input_1, $input_2);
        break;
    }

    if (isset($result)) {
      $this->setProvidedValue('result', $result);
    }
  }

}
