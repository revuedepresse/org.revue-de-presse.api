<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Stateless principal identified by a Bluesky DID. The chat firewall
 * builds this from the verified JWT; no DB lookup, no Doctrine entity.
 */
final readonly class BlueskyChatUser implements UserInterface
{
    public function __construct(
        public string $did,
        public ?string $handle = null,
    ) {
    }

    public function getRoles(): array
    {
        return ['ROLE_BSKY_USER'];
    }

    public function getUserIdentifier(): string
    {
        return $this->did;
    }

    public function eraseCredentials(): void
    {
    }
}
