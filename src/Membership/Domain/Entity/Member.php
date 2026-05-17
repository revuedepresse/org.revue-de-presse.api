<?php
declare(strict_types=1);

namespace App\Membership\Domain\Entity;

use App\Twitter\Infrastructure\Repository\Membership\MemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: MemberRepository::class)]
#[ORM\Table(name: 'weaving_user')]
class Member implements UserInterface, Stringable
{
    #[ORM\Id]
    #[ORM\Column(name: 'usr_id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'usr_api_key', type: 'string', nullable: true)]
    public ?string $apiKey = null;

    #[ORM\Column(name: 'usr_user_name', type: 'string', length: 255, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(name: 'usr_username_canonical', type: 'string', length: 255, nullable: true)]
    private ?string $usernameCanonical = null;

    #[ORM\Column(name: 'usr_status', type: 'boolean')]
    private bool $enabled = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function setUsernameCanonical(?string $usernameCanonical): self
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }
}
