<?php
declare(strict_types=1);

namespace App\Membership\Domain\Model;

use App\Twitter\Domain\Http\Model\TokenInterface;
use function mt_rand;
use function sha1;
use function uniqid;

abstract class Member implements MemberInterface
{
    protected ?int $id;

    protected ?string $emailCanonical;

    protected ?string $username;

    protected ?string $usernameCanonical;

    protected bool $enabled;

    protected int $positionInHierarchy;

    /**
     * The salt to use for hashing
     */
    protected string $salt;

    /**
     * Encrypted password. Must be persisted.
     */
    protected string $password;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     */
    protected ?string $plainPassword;

    protected string $email;

    /**
     */
    public function __construct()
    {
        $random = (string) mt_rand();
        $uniqueId = uniqid($random, true);
        $hash = sha1($uniqueId);
        $this->salt = base_convert($hash, 16, 36);
        $this->enabled = false;
        $this->positionInHierarchy = 1;
    }

    /**
     * Serializes the user.
     *
     * The serialized data have to contain the fields used by the equals method and the username.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize([
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id,
        ]);
    }

    /**
     * Unserializes the user.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        // add a few extra elements in the array to ensure that we have enough keys when unserializing
        // older data which does not include all properties.
        $data = array_merge($data, array_fill(0, 2, null));

        [
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id
        ] = $data;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsernameCanonical(?string $usernameCanonical): MemberInterface
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    public function getUsernameCanonical(): string
    {
        return $this->usernameCanonical;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): MemberInterface
    {
        $this->email = $email;

        return $this;
    }

    public function getEmailCanonical(): string
    {
        return $this->emailCanonical;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isSameMemberThan(?MemberInterface $user = null): bool
    {
        return null !== $user && $this->getId() === $user->getId();
    }

    public function setUsername(string $username): MemberInterface
    {
        $this->username = $username;

        return $this;
    }

    public function setEmailCanonical(string $emailCanonical): MemberInterface
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    public function setEnabled(bool $enabled): MemberInterface
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function __toString()
    {
        return (string) $this->getUsername();
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function addToken(TokenInterface $token): MemberInterface
    {
        $tokenToBeRevised = $this->tokens->filter(function (TokenInterface $existingToken) use ($token) {
            return $existingToken->getConsumerKey() === $token->getConsumerKey() &&
                $existingToken->getConsumerSecret() === $token->getConsumerSecret();
        })->first();

        $this->tokens->map(function (TokenInterface $token) use ($tokenToBeRevised) {
            if ($token === $tokenToBeRevised) {
                $token->setAccessToken($token->getAccessToken());
                $token->setAccessTokenSecret($token->getAccessTokenSecret());
                $token->setUpdatedAt($token->updatedAt());
            }
        });

        if ($tokenToBeRevised) {
            return $this;
        }

        $this->tokens[] = $token;

        return $this;
    }
}
