<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_id\Unit\OAuth2\Provider;

use Drupal\acquia_id\OAuth2\Provider\AcquiaIdProvider;
use Drupal\Tests\UnitTestCase;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Documents this element.
 */
#[CoversClass(AcquiaIdProvider::class)]
#[Group('acquia_id')]
class AcquiaIdProviderTest extends UnitTestCase {

  /**
   * The provider under test.
   */
  private AcquiaIdProvider $provider;

  /**
   * Documents this element.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->provider = new AcquiaIdProvider([
      'clientId' => 'test-client',
      'redirectUri' => 'https://example.com/callback',
    ]);
    $this->provider
      ->setIdpBaseUri('https://id.acquia.com/oauth2/default')
      ->setCloudApiBaseUri('https://cloud.acquia.com');
  }

  /**
   * Documents this element.
   */
  public function testBaseAuthorizationUrl(): void {
    $this->assertSame(
      'https://id.acquia.com/oauth2/default/v1/authorize',
      $this->provider->getBaseAuthorizationUrl(),
    );
  }

  /**
   * Documents this element.
   */
  public function testBaseAccessTokenUrl(): void {
    $this->assertSame(
      'https://id.acquia.com/oauth2/default/v1/token',
      $this->provider->getBaseAccessTokenUrl([]),
    );
  }

  /**
   * Documents this element.
   */
  public function testResourceOwnerDetailsUrl(): void {
    $token = new AccessToken(['access_token' => 'test', 'expires_in' => 3600]);
    $this->assertSame(
      'https://cloud.acquia.com/api/account',
      $this->provider->getResourceOwnerDetailsUrl($token),
    );
  }

  /**
   * Documents this element.
   */
  public function testAuthorizationUrlContainsExpectedScopes(): void {
    $url = $this->provider->getAuthorizationUrl();
    $query = parse_url($url, PHP_URL_QUERY);
    parse_str($query, $params);

    $this->assertStringContainsString('openid', $params['scope']);
    $this->assertStringContainsString('email', $params['scope']);
    $this->assertStringContainsString('profile', $params['scope']);
    $this->assertStringContainsString('offline_access', $params['scope']);
  }

  /**
   * Documents this element.
   */
  public function testAuthorizationUrlIncludesPkceChallenge(): void {
    $url = $this->provider->getAuthorizationUrl();
    $query = parse_url($url, PHP_URL_QUERY);
    parse_str($query, $params);

    $this->assertArrayHasKey('code_challenge', $params);
    $this->assertSame('S256', $params['code_challenge_method']);
  }

}
