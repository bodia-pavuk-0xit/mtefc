<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Core\Attribute\RulesAction;
use Drupal\rules\Core\RulesActionBase;
use Drupal\rules\TypedData\Options\LanguageOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides "Send email" rules action.
 *
 * @todo Define that message Context should be textarea comparing with textfield Subject
 * @todo Add access callback information from Drupal 7.
 *
 * @RulesAction(
 *   id = "rules_send_email",
 *   label = @Translation("Send email"),
 *   category = @Translation("System"),
 *   context_definitions = {
 *     "to" = @ContextDefinition("email",
 *       label = @Translation("Send to"),
 *       description = @Translation("Email address(es) drupal will send an email to."),
 *       multiple = TRUE
 *     ),
 *     "subject" = @ContextDefinition("string",
 *       label = @Translation("Subject"),
 *       description = @Translation("The email's subject.")
 *     ),
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message"),
 *       description = @Translation("The email's message body. Drupal will by default remove all HTML tags. If you want to use HTML you must override this behavior by installing a contributed module such as Mime Mail.")
 *     ),
 *     "reply" = @ContextDefinition("email",
 *       label = @Translation("Reply to"),
 *       description = @Translation("The email's reply-to address. Leave it empty to use the site-wide configured address."),
 *       default_value = NULL,
 *       required = FALSE
 *     ),
 *     "language" = @ContextDefinition("language",
 *       label = @Translation("Language"),
 *       description = @Translation("If specified, the language used for getting the email message and subject."),
 *       options_provider = "\Drupal\rules\TypedData\Options\LanguageOptions",
 *       default_value = NULL,
 *       required = FALSE
 *     ),
 *   }
 * )
 */
#[RulesAction(
  id: "rules_send_email",
  label: new TranslatableMarkup("Send email"),
  category: new TranslatableMarkup("System"),
  context_definitions: [
    "to" => new ContextDefinition(
      data_type: "email",
      label: new TranslatableMarkup("Send to"),
      description: new TranslatableMarkup("Email address(es) drupal will send an email to."),
      multiple: TRUE
    ),
    "subject" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("Subject"),
      description: new TranslatableMarkup("The email's subject.")
    ),
    "message" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("Message"),
      description: new TranslatableMarkup("The email's message body. Drupal will by default remove all HTML tags. If you want to use HTML you must override this behavior by installing a contributed module such as Mime Mail.")
    ),
    "reply" => new ContextDefinition(
      data_type: "email",
      label: new TranslatableMarkup("Reply to"),
      description: new TranslatableMarkup("The email's reply-to address. Leave it empty to use the site-wide configured address."),
      default_value: NULL,
      required: FALSE
    ),
    "language" => new ContextDefinition(
      data_type: "language",
      label: new TranslatableMarkup("Language"),
      description: new TranslatableMarkup("If specified, the language object (not language code) used for getting the email message and subject."),
      options_provider: LanguageOptions::class,
      default_value: NULL,
      required: FALSE
    ),
  ]
)]
class SystemSendEmail extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The logger channel the action will write log messages to.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a SystemSendEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The Rules logger channel.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger, MailManagerInterface $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('rules'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * Send a system email.
   *
   * @param string[] $to
   *   Email addresses of the recipients.
   * @param string $subject
   *   Subject of the email.
   * @param string $message
   *   Email message text.
   * @param string|null $reply
   *   (optional) Reply to email address.
   * @param \Drupal\Core\Language\LanguageInterface|null $language
   *   (optional) Language code.
   */
  protected function doExecute(array $to, $subject, $message, $reply = NULL, LanguageInterface $language = NULL) {
    // ORIG.
    $langcode = isset($language) ? $language->getId() : LanguageInterface::LANGCODE_SITE_DEFAULT;
    // @todo Is this better?
    $langcode = (isset($language) && $language->getId() != LanguageInterface::LANGCODE_NOT_SPECIFIED) ? $language->getId() : LanguageInterface::LANGCODE_SITE_DEFAULT;
    $params = [
      'subject' => $subject,
      'message' => $message,
    ];
    // Set a unique key for this email.
    $key = 'rules_action_mail_' . $this->getPluginId();

    $recipients = implode(', ', $to);
    $message = $this->mailManager->mail('rules', $key, $recipients, $langcode, $params, $reply);
    if ($message['result']) {
      $this->logger->notice('Successfully sent email to %recipient', ['%recipient' => $recipients]);
    }

  }

}
