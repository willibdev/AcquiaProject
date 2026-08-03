<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_id\Unit\OAuth2\Provider;

use Drupal\acquia_id\OAuth2\Provider\AcquiaIdResourceOwner;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Documents this element.
 */
#[CoversClass(AcquiaIdResourceOwner::class)]
#[Group('acquia_id')]
class AcquiaIdResourceOwnerTest extends UnitTestCase {

  /**
   * Documents this element.
   */
  public function testGetIdReturnsMail(): void {
    $owner = new AcquiaIdResourceOwner([
      'mail' => 'user@example.com',
      'uuid' => 'abc-123',
      'timezone' => 'America/New_York',
    ]);
    $this->assertSame('user@example.com', $owner->getId());
  }

  /**
   * Documents this element.
   */
  public function testToArrayReturnsFullResponse(): void {
    $data = [
      'mail' => 'user@example.com',
      'uuid' => 'abc-123',
      'timezone' => 'America/New_York',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
    ];
    $owner = new AcquiaIdResourceOwner($data);
    $this->assertSame($data, $owner->toArray());
  }

}
