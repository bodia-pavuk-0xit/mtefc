<?php

namespace Drupal\rules\Plugin\RulesExpression;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rules\Attribute\RulesExpression;
use Drupal\rules\Context\DataProcessorManager;
use Drupal\rules\Context\ExecutionMetadataStateInterface;
use Drupal\rules\Context\ExecutionStateInterface;
use Drupal\rules\Core\RulesActionManagerInterface;
use Drupal\rules\Engine\ActionExpressionInterface;
use Drupal\rules\Engine\ExpressionBase;
use Drupal\rules\Engine\ExpressionInterface;
use Drupal\rules\Form\Expression\ActionForm;
use Drupal\rules\Context\ContextHandlerIntegrityTrait;
use Drupal\rules\Engine\IntegrityViolationList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an executable action expression.
 *
 * This plugin is used to wrap action plugins and is responsible to setup all
 * the context necessary, instantiate the action plugin and to execute it.
 *
 * @RulesExpression(
 *   id = "rules_action",
 *   label = @Translation("Action"),
 *   form_class = "\Drupal\rules\Form\Expression\ActionForm"
 * )
 */
#[RulesExpression(
  id: "rules_action",
  label: new TranslatableMarkup("Action"),
  form_class: ActionForm::class
)]
class ActionExpression extends ExpressionBase implements ContainerFactoryPluginInterface, ActionExpressionInterface {
  use ContextHandlerIntegrityTrait;

  /**
   * The action manager used to instantiate the action plugin.
   *
   * @var \Drupal\rules\Core\RulesActionManagerInterface
   */
  protected $actionManager;

  /**
   * The rules debug logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $rulesDebugLogger;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   *   Contains the following entries:
   *   - action_id: The action plugin ID.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rules\Core\RulesActionManagerInterface $action_manager
   *   The Rules action manager.
   * @param \Drupal\rules\Context\DataProcessorManager $processor_manager
   *   The data processor plugin manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The Rules debug logger channel.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RulesActionManagerInterface $action_manager, DataProcessorManager $processor_manager, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->actionManager = $action_manager;
    $this->processorManager = $processor_manager;
    $this->rulesDebugLogger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.rules_action'),
      $container->get('plugin.manager.rules_data_processor'),
      $container->get('logger.channel.rules_debug')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // If the plugin id has been set already, keep it if not specified.
    if (isset($this->configuration['action_id'])) {
      $configuration += [
        'action_id' => $this->configuration['action_id'],
      ];
    }
    return parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function executeWithState(ExecutionStateInterface $state) {
    $this->rulesDebugLogger->info('Evaluating the action %name.', [
      '%name' => $this->getLabel(),
      'element' => $this,
    ]);
    $action = $this->actionManager->createInstance($this->configuration['action_id']);

    $this->prepareContext($action, $state);
    $action->execute();

    $auto_saves = $action->autoSaveContext();
    foreach ($auto_saves as $context_name) {
      // Mark parameter contexts for auto saving in the Rules state.
      $state->saveChangesLater($this->configuration['context_mapping'][$context_name]);
    }

    // Now that the action has been executed it can provide additional
    // context which we will have to pass back in the evaluation state.
    $this->addProvidedContext($action, $state);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    if (!empty($this->configuration['action_id'])) {
      $definition = $this->actionManager->getDefinition($this->configuration['action_id']);
      return $definition['label'];
    }
    return parent::getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormHandler() {
    if (isset($this->pluginDefinition['form_class'])) {
      $class_name = $this->pluginDefinition['form_class'];
      return new $class_name($this, $this->actionManager);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkIntegrity(ExecutionMetadataStateInterface $metadata_state, $apply_assertions = TRUE) {
    $violation_list = new IntegrityViolationList();
    if (empty($this->configuration['action_id'])) {
      $violation_list->addViolationWithMessage($this->t('Action plugin ID is missing'), $this->getUuid());
      return $violation_list;
    }
    if (!$this->actionManager->hasDefinition($this->configuration['action_id'])) {
      $violation_list->addViolationWithMessage($this->t('Action plugin %plugin_id does not exist', [
        '%plugin_id' => $this->configuration['action_id'],
      ]), $this->getUuid());
      return $violation_list;
    }

    $action = $this->actionManager->createInstance($this->configuration['action_id']);

    // Prepare and refine the context before checking integrity, such that any
    // context definition changes are respected while checking.
    $this->prepareContextWithMetadata($action, $metadata_state);
    $result = $this->checkContextConfigIntegrity($action, $metadata_state);
    $this->prepareExecutionMetadataState($metadata_state, NULL, $apply_assertions);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareExecutionMetadataState(ExecutionMetadataStateInterface $metadata_state, ExpressionInterface $until = NULL, $apply_assertions = TRUE) {
    if ($until && $this->getUuid() === $until->getUuid()) {
      return TRUE;
    }
    $action = $this->actionManager->createInstance($this->configuration['action_id']);
    // Make sure to refine context first, such that possibly refined definitions
    // of provided context are respected.
    $this->prepareContextWithMetadata($action, $metadata_state);
    $this->addProvidedContextDefinitions($action, $metadata_state);
    if ($apply_assertions) {
      $this->assertMetadata($action, $metadata_state);
    }
  }

}
