<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Core\Attribute\RulesAction;
use Drupal\rules\Core\RulesActionBase;
use Drupal\user\UserInterface;

/**
 * Provides "Unblock User" action.
 *
 * @todo Add access callback information from Drupal 7.
 *
 * @RulesAction(
 *   id = "rules_user_unblock",
 *   label = @Translation("Unblock a user"),
 *   category = @Translation("User"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       description = @Translation("Specifies the user that should be unblocked.")
 *     ),
 *   }
 * )
 */
#[RulesAction(
  id: "rules_user_unblock",
  label: new TranslatableMarkup("Unblock a user"),
  category: new TranslatableMarkup("User"),
  context_definitions: [
    "user" => new ContextDefinition(
      data_type: "entity:user",
      label: new TranslatableMarkup("User"),
      description: new TranslatableMarkup("Specifies the user that should be unblocked.")
    ),
  ]
)]
class UserUnblock extends RulesActionBase {
  /**
   * Flag that indicates if the entity should be auto-saved later.
   *
   * @var bool
   */
  protected $saveLater = FALSE;

  /**
   * Unblock a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to unblock.
   */
  protected function doExecute(UserInterface $user) {
    // Do nothing if user is anonymous or isn't blocked.
    if ($user->isAuthenticated() && $user->isBlocked()) {
      $user->activate();
      // Set flag that indicates if the entity should be auto-saved later.
      $this->saveLater = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function autoSaveContext() {
    if ($this->saveLater) {
      return ['user'];
    }
    return [];
  }

}
