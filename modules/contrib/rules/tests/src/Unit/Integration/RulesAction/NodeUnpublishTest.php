<?php

declare(strict_types=1);

namespace Drupal\Tests\rules\Unit\Integration\RulesAction;

use Drupal\node\NodeInterface;
use Drupal\Tests\rules\Unit\Integration\RulesEntityIntegrationTestBase;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\RulesAction\NodeUnpublish
 * @group RulesAction
 */
class NodeUnpublishTest extends RulesEntityIntegrationTestBase {

  /**
   * The action to be tested.
   *
   * @var \Drupal\rules\Core\RulesActionInterface
   */
  protected $action;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->action = $this->actionManager->createInstance('rules_node_unpublish');
  }

  /**
   * Tests the summary.
   *
   * @covers ::summary
   */
  public function testSummary(): void {
    $this->assertEquals('Unpublish a content item', $this->action->summary());
  }

  /**
   * Tests the action execution.
   *
   * @covers ::execute
   */
  public function testActionExecution(): void {
    $node = $this->prophesizeEntity(NodeInterface::class);
    $node->setUnpublished()->shouldBeCalledTimes(1);

    $this->action->setContextValue('node', $node->reveal());
    $this->action->execute();

    $this->assertEquals(
      ['node'],
      $this->action->autoSaveContext(),
      'Action returns the user context name for auto saving.'
    );
  }

}
