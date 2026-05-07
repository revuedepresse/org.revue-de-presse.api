<?php
declare(strict_types=1);

namespace App\Membership\Domain\Model;

use App\Membership\Domain\Entity\MemberInterface;
use function mt_rand;
use function sha1;
use function uniqid;

abstract class Member implements MemberInterface, \Stringable
{
    /**
     * @var
     */
    protected ?int $id = null;

    /**
     * @var string
     */
    protected ?string $emailCanonical = null;

    /**
     * @var string
     */
    protected ?string $username = null;

    /**
     * @var string
     */
    protected ?string $usernameCanonical = null;

    /**
     * @var boolean
     */
    protected bool $enabled;

    /**
     * @var integer
     */
    protected int $positionInHierarchy;

    /**
     * The salt to use for hashing
     *
     * @var string
     */
    protected string $salt;

    /**
     * Encrypted password. Must be persisted.
     *
     * @var string
     */
    protected string $password;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected ?string $plainPassword = null;

    /**
     * @var string
     */
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

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier() instead
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->getUserIdentifier();
    }

    /**
     * @param $usernameCanonical
     * @return $this
     */
    public function setUsernameCanonical($usernameCanonical): self
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsernameCanonical(): string
    {
        return $this->usernameCanonical;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailCanonical(): string
    {
        return $this->emailCanonical;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param MemberInterface|null $user
     * @return bool
     */
    public function isSameMemberThan(MemberInterface $user = null): bool
    {
        return null !== $user && $this->getId() === $user->getId();
    }

    /**
     * @param $username
     * @return $this
     */
    public function setUsername($username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @param $emailCanonical
     * @return $this
     */
    public function setEmailCanonical(string $emailCanonical): self
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->getUserIdentifier();
    }

    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }
}
