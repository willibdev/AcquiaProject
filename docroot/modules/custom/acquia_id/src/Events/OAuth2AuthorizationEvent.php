<?php

declare(strict_types=1);

namespace Drupal\acquia_id\Events;

use Drupal\acquia_id\OAuth2\Provider\IdpProvider;
use Drupal\user\UserInterface;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Documents this element.
 */
final class OAuth2AuthorizationEvent extends Event {

  /**
   * The authenticated Drupal user, if available.
   */
  private UserInterface|null $user = NULL;

  public function __construct(
    public readonly IdpProvider $provider,
    public readonly AccessToken $accessToken,
  ) {
  }

  /**
   * Documents this element.
   */
  public function setUser(UserInterface $user): void {
    $this->user = $user;
  }

  /**
   * Documents this element.
   */
  public function getUser(): ?UserInterface {
    return $this->user;
  }

}
