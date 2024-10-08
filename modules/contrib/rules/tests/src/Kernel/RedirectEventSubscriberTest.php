<?php

declare(strict_types=1);

namespace Drupal\Tests\rules\Kernel;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests rules redirect action event subscriber.
 *
 * @coversDefaultClass \Drupal\rules\EventSubscriber\RedirectEventSubscriber
 *
 * @group RulesEvent
 */
class RedirectEventSubscriberTest extends RulesKernelTestBase {

  /**
   * Test the response is a redirect if a redirect url is added to the request.
   *
   * @covers ::checkRedirectIssued
   */
  public function testCheckRedirectIssued(): void {
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = $this->container->get('http_kernel');

    $request = Request::create('/');
    $request->attributes->set('_rules_redirect_action_url', '/test/redirect/url');

    $response = $http_kernel->handle($request);

    $this->assertInstanceOf(RedirectResponse::class, $response, "The response is a redirect.");
    $this->assertEquals('/test/redirect/url', $response->getTargetUrl(), "The redirect target is the provided url.");
  }

}
