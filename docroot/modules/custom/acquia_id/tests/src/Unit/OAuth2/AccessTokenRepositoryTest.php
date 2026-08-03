<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_id\Unit\OAuth2;

use Drupal\acquia_id\OAuth2\AccessTokenRepository;
use Drupal\acquia_id\OAuth2\Provider\AcquiaIdProvider;
use Drupal\acquia_id\OAuth2\ProviderFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserDataInterface;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Documents this element.
 */
#[CoversClass(AccessTokenRepository::class)]
#[Group('acquia_id')]
class AccessTokenRepositoryTest extends UnitTestCase {

  /**
   * The mocked user data service.
   */
  private UserDataInterface $userData;

  /**
   * The mocked time service.
   */
  private TimeInterface $time;

  /**
   * The mocked provider factory.
   */
  private ProviderFactory $providerFactory;

  /**
   * The repository under test.
   */
  private AccessTokenRepository $repository;

  /**
   * Documents this element.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->userData = $this->createMock(UserDataInterface::class);
    $this->time = $this->createMock(TimeInterface::class);
    $this->providerFactory = $this->createMock(ProviderFactory::class);
    $this->repository = new AccessTokenRepository(
      $this->providerFactory,
      $this->userData,
      $this->time,
    );
  }

  /**
   * Documents this element.
   */
  public function testStoreWritesTokenToUserData(): void {
    $token = new AccessToken(['access_token' => 'tok-abc', 'expires_in' => 3600]);
    $this->time->method('getCurrentTime')->willReturn(1000000);

    $this->userData->expects($this->once())
      ->method('set')
      ->with('acquia_id', 42, 'acquia_id_access_token', [
        'access_token' => $token,
        'timestamp' => 1000000,
      ]);

    $this->repository->store(42, $token);
  }

  /**
   * Documents this element.
   */
  public function testGetReturnsNullWhenNoTokenStored(): void {
    $this->userData->method('get')
      ->with('acquia_id', 42, 'acquia_id_access_token')
      ->willReturn(NULL);

    $this->assertNull($this->repository->get(42));
  }

  /**
   * Documents this element.
   */
  public function testGetReturnsNullWhenRefreshTokenTtlExceeded(): void {
    $now = 1000000;
    // Stored 91 minutes ago (exceeds 90-minute refresh TTL).
    $storedAt = $now - (91 * 60);

    $this->time->method('getCurrentTime')->willReturn($now);
    $this->userData->method('get')
      ->with('acquia_id', 42, 'acquia_id_access_token')
      ->willReturn([
        'access_token' => new AccessToken([
          'access_token' => 'tok-old',
          'expires_in' => 3600,
        ]),
        'timestamp' => $storedAt,
      ]);

    $this->assertNull($this->repository->get(42));
  }

  /**
   * Documents this element.
   */
  public function testGetReturnsTokenWhenValid(): void {
    $now = 1000000;
    $token = new AccessToken([
      'access_token' => 'tok-valid',
      // Expires well in the future.
      'expires' => $now + 3600,
    ]);

    $this->time->method('getCurrentTime')->willReturn($now);
    $this->userData->method('get')
      ->with('acquia_id', 42, 'acquia_id_access_token')
      ->willReturn([
        'access_token' => $token,
        'timestamp' => $now,
      ]);

    $result = $this->repository->get(42);
    $this->assertSame($token, $result);
  }

  /**
   * Documents this element.
   */
  public function testGetRefreshesExpiredToken(): void {
    $now = 1000000;
    $expiredToken = new AccessToken([
      'access_token' => 'tok-expired',
      // Already expired (negative TTL).
      'expires_in' => -10,
      'refresh_token' => 'refresh-123',
    ]);
    // Stored recently (within 90-minute window).
    $storedAt = $now - 60;

    $this->time->method('getCurrentTime')->willReturn($now);
    $this->userData->method('get')
      ->with('acquia_id', 42, 'acquia_id_access_token')
      ->willReturn([
        'access_token' => $expiredToken,
        'timestamp' => $storedAt,
      ]);

    $refreshedToken = new AccessToken([
      'access_token' => 'tok-refreshed',
      'expires' => $now + 3600,
    ]);

    $provider = $this->createMock(AcquiaIdProvider::class);
    $provider->expects($this->once())
      ->method('getAccessToken')
      ->with('refresh_token', ['refresh_token' => 'refresh-123'])
      ->willReturn($refreshedToken);

    $this->providerFactory->method('get')->willReturn($provider);

    // Expect the refreshed token to be stored.
    $this->userData->expects($this->once())
      ->method('set')
      ->with('acquia_id', 42, 'acquia_id_access_token', [
        'access_token' => $refreshedToken,
        'timestamp' => $now,
      ]);

    $result = $this->repository->get(42);
    $this->assertSame('tok-refreshed', $result->getToken());
  }

  /**
   * Documents this element.
   */
  public function testDeleteRemovesTokenData(): void {
    $this->userData->expects($this->once())
      ->method('delete')
      ->with('acquia_id', 42, 'acquia_id_access_token');

    $this->repository->delete(42);
  }

  /**
   * Documents this element.
   */
  public function testGetUserIdsWithTokens(): void {
    $this->userData->method('get')
      ->with('acquia_id', NULL, 'acquia_id_access_token')
      ->willReturn([1 => 'data1', 5 => 'data2', 12 => 'data3']);

    $this->assertSame([1, 5, 12], $this->repository->getUserIdsWithTokens());
  }

}
