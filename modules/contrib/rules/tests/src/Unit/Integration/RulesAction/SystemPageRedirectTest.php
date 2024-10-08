<?php

declare(strict_types=1);

namespace Drupal\Tests\rules\Unit\Integration\RulesAction {

  use Drupal\Tests\rules\Unit\Integration\RulesIntegrationTestBase;
  use Drupal\Core\Logger\LoggerChannelInterface;
  use Drupal\Core\Logger\LoggerChannelFactoryInterface;
  use Drupal\Core\Path\CurrentPathStack;
  use Symfony\Component\HttpFoundation\ParameterBag;
  use Symfony\Component\HttpFoundation\Request;
  use Symfony\Component\HttpFoundation\RequestStack;

  /**
   * @coversDefaultClass \Drupal\rules\Plugin\RulesAction\SystemPageRedirect
   * @group RulesAction
   */
  class SystemPageRedirectTest extends RulesIntegrationTestBase {

    /**
     * A mocked Rules logger.channel.rules_debug service.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ProphecyInterface
     */
    protected $logger;

    /**
     * The mocked request stack service.
     *
     * @var \Symfony\Component\HttpFoundation\RequestStack|\Prophecy\Prophecy\ProphecyInterface
     */
    protected $requestStack;

    /**
     * The mocked current path stack service.
     *
     * @var \Drupal\Core\Path\CurrentPathStack|\Prophecy\Prophecy\ProphecyInterface
     */
    protected $currentPathStack;

    /**
     * A mocked request.
     *
     * @var \Symfony\Component\HttpFoundation\Request|\Prophecy\Prophecy\ProphecyInterface
     */
    protected $currentRequest;

    /**
     * A mocked parameter bag.
     *
     * @var \Symfony\Component\HttpFoundation\ParameterBag|\Prophecy\Prophecy\ProphecyInterface
     */
    protected $parameterBag;

    /**
     * The action to be tested.
     *
     * @var \Drupal\rules\Plugin\RulesAction\SystemPageRedirect
     */
    protected $action;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void {
      parent::setUp();

      // Mock the Rules debug logger service, make it return our mocked logger,
      // and register it in the container.
      $this->logger = $this->prophesize(LoggerChannelInterface::class);
      $this->container->set('logger.channel.rules_debug', $this->logger->reveal());

      // Mock a parameter bag.
      $this->parameterBag = $this->prophesize(ParameterBag::class);

      // Mock a request, and set our mocked parameter bag as it attributes
      // property.
      $this->currentRequest = $this->prophesize(Request::class);
      $this->currentRequest->attributes = $this->parameterBag->reveal();

      // Mock the request stack, make it return our mocked request when the
      // current request is requested, and register it in the container.
      $this->requestStack = $this->prophesize(RequestStack::class);
      $this->requestStack->getCurrentRequest()->willReturn($this->currentRequest);
      $this->container->set('request_stack', $this->requestStack->reveal());

      // Mock the current path stack.
      $this->currentPathStack = $this->prophesize(CurrentPathStack::class);
      $this->container->set('path.current', $this->currentPathStack->reveal());

      // Instantiate the redirect action.
      $this->action = $this->actionManager->createInstance('rules_page_redirect');
    }

    /**
     * Tests redirection.
     *
     * @covers ::execute
     */
    public function testRedirect(): void {
      $this->currentPathStack->getPath()->willReturn('some/random/test/path');

      $this->action->setContextValue('url', '/test/url');
      $this->action->execute();

      $this->parameterBag->set('_rules_redirect_action_url', '/test/url')->shouldHaveBeenCalled();
    }

    /**
     * Tests unsuccessful redirection due to ongoing batch process.
     *
     * @covers ::execute
     */
    public function testRedirectBatch(): void {
      $this->currentPathStack->getPath()->willReturn('some/random/test/path');
      batch_set('Test batch!');

      $this->action->setContextValue('url', '/test/url');
      $this->action->execute();

      $this->logger->warning('Skipped page redirect during batch processing.')->shouldHaveBeenCalled();
    }

    /**
     * Tests unsuccessful redirection due to rules admin page location.
     *
     * @covers ::execute
     */
    public function testRedirectRulesAdminPage(): void {
      $this->currentPathStack->getPath()->willReturn('admin/config/workflow/rules');

      $this->action->setContextValue('url', '/test/url');
      $this->action->execute();

      $this->logger->warning('Skipped page redirect on a rules admin page.')->shouldHaveBeenCalled();
    }

  }
}

namespace {

  if (!function_exists('batch_get')) {

    /**
     * Mock the batch_set() function.
     */
    function batch_set($batch_definition) {
      if ($batch_definition) {
        $batch = &batch_get();
        // Nothing more than current_set should be mocked for testing purposes.
        $batch['current_set'] = $batch_definition;
      }
    }

    /**
     * Mock the batch_get() function.
     */
    function &batch_get() {
      static $batch = [];
      return $batch;
    }

  }

}
