<?php

declare(strict_types=1);

namespace Drupal\Tests\typed_data\Functional\TypedDataFormWidget;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Tests operation of the 'textarea' TypedDataForm widget plugin.
 *
 * @group typed_data
 *
 * @coversDefaultClass \Drupal\typed_data\Plugin\TypedDataFormWidget\TextareaWidget
 */
class TextareaWidgetTest extends FormWidgetBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createWidget('textarea');
  }

  /**
   * @covers ::isApplicable
   */
  public function testIsApplicable(): void {
    $this->assertFalse($this->widget->isApplicable(DataDefinition::create('any')));
    $this->assertFalse($this->widget->isApplicable(DataDefinition::create('binary')));
    $this->assertFalse($this->widget->isApplicable(DataDefinition::create('boolean')));
    $this->assertFalse($this->widget->isApplicable(DataDefinition::create('datetime_iso8601')));
    $this->assertFalse($this->widget->isApplicable(DataDefinition::create('duration_iso8601')));
    $this->assertFalse($this->widget->isApplicable(DataDefinition::create('email')));
    $this->assertFalse($this->widget->isApplicable(DataDefinition::create('float')));
    $this->assertFalse($this->widget->isApplicable(DataDefinition::create('integer')));
    $this->assertTrue($this->widget->isApplicable(DataDefinition::create('string')));
    $this->assertFalse($this->widget->isApplicable(DataDefinition::create('timespan')));
    $this->assertFalse($this->widget->isApplicable(DataDefinition::create('timestamp')));
    $this->assertFalse($this->widget->isApplicable(DataDefinition::create('uri')));
    $this->assertFalse($this->widget->isApplicable(ListDataDefinition::create('string')));
    $this->assertFalse($this->widget->isApplicable(MapDataDefinition::create()));
  }

  /**
   * @covers ::form
   * @covers ::extractFormValues
   */
  public function testFormEditing(): void {
    $context_definition = ContextDefinition::create('string')
      ->setLabel('Example textarea')
      ->setDescription('Some example textarea')
      ->setDefaultValue('A string longer than eight characters');
    $this->container->get('state')->set('typed_data_widgets.definition', $context_definition);

    $path = 'admin/config/user-interface/typed-data-widgets/' . $this->widget->getPluginId();
    $this->drupalGet($path);

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();
    $assert->elementTextContains('css', 'label[for=edit-data-value]', $context_definition->getLabel());
    $assert->elementTextContains('css', 'div[id=edit-data-value--description]', $context_definition->getDescription());
    $assert->fieldValueEquals('data[value]', $context_definition->getDefaultValue());

    $this->fillField('data[value]', 'jump');
    $this->pressButton('Submit');

    $this->drupalGet($path);
    $assert->fieldValueEquals('data[value]', 'jump');
  }

  /**
   * @covers ::form
   * @covers ::flagViolations
   */
  public function testValidation(): void {
    $context_definition = ContextDefinition::create('text')
      ->setLabel('Test text area')
      ->setDescription('Enter text, minimum 40 characters.');
    $context_definition->addConstraint('Length', ['min' => 40]);

    $this->container->get('state')->set('typed_data_widgets.definition', $context_definition);

    $path = 'admin/config/user-interface/typed-data-widgets/' . $this->widget->getPluginId();
    $this->drupalGet($path);

    // Try to save with text that is too short.
    $this->fillField('data[value]', $this->randomString(20));
    $this->pressButton('Submit');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();
    $assert->fieldExists('data[value]')->hasClass('error');

    // Make sure the changes have not been saved.
    $this->drupalGet($path);
    $assert->fieldValueEquals('data[value]', $context_definition->getDefaultValue());
  }

}
