<?php

declare(strict_types=1);

namespace Drupal\Tests\rules\Unit\Integration\RulesAction;

use Drupal\Tests\rules\Unit\Integration\RulesEntityIntegrationTestBase;
use Drupal\user\UserInterface;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\RulesAction\UserUnblock
 * @group RulesAction
 */
class UserUnblockTest extends RulesEntityIntegrationTestBase {

  /**
   * Constant used for authenticated test when mocking a user.
   */
  const AUTHENTICATED = TRUE;

  /**
   * Constant used for authenticated test when mocking a user.
   */
  const ANONYMOUS = FALSE;

  /**
   * Constant used for active test when mocking a user.
   */
  const ACTIVE = TRUE;

  /**
   * Constant used for active test when mocking a user.
   */
  const BLOCKED = FALSE;

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
    $this->enableModule('user');
    $this->action = $this->actionManager->createInstance('rules_user_unblock');
  }

  /**
   * Test the summary method.
   *
   * @covers ::summary
   */
  public function testSummary(): void {
    $this->assertEquals('Unblock a user', $this->action->summary());
  }

  /**
   * Test execute() method for users with different status.
   *
   * @dataProvider userProvider
   *
   * @covers ::execute
   */
  public function testUnblockUser(bool $active, bool $authenticated, int $expects, array $autosave_names): void {
    // Set-up a mock user.
    $account = $this->prophesizeEntity(UserInterface::class);
    // Mock isBlocked.
    $account->isBlocked()->willReturn(!$active);
    // Mock isAuthenticated.
    $account->isAuthenticated()->willReturn($authenticated);
    // Mock activate.
    $account->activate()->shouldBeCalledTimes($expects);
    // We do not expect to call the 'save' method because the user should be
    // auto-saved later.
    $account->save()->shouldNotBeCalled();
    // Test unblocking the user.
    $this->action
      ->setContextValue('user', $account->reveal())
      ->execute();

    $this->assertEquals($this->action->autoSaveContext(), $autosave_names, 'Action returns correct context name for auto saving.');
  }

  /**
   * Data provider for ::testUnblockUser.
   */
  public static function userProvider(): array {
    return [
      // Test blocked authenticated user.
      [self::BLOCKED, self::AUTHENTICATED, 1, ['user']],
      // Test active anonymous user.
      [self::ACTIVE, self::ANONYMOUS, 0, []],
      // Test active authenticated user.
      [self::ACTIVE, self::AUTHENTICATED, 0, []],
      // Test blocked anonymous user.
      [self::BLOCKED, self::ANONYMOUS, 0, []],
    ];
  }

}
