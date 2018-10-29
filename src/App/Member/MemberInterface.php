<?php

namespace App\Member;

interface MemberInterface
{
    /**
     * @return string
     */
    public function getApiKey(): string;

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @param int $groupId
     * @return MemberInterface
     */
    public function setGroupId(int $groupId): self;

    /**
     * @return string
     */
    public function getUsername();

    /**
     * @param string $firstName
     * @return MemberInterface
     */
    public function setFirstName(string $firstName): self;

    /**
     * @return string
     */
    public function getFirstName(): string;

    /**
     * @param string $lastName
     * @return MemberInterface
     */
    public function setLastName(string $lastName): self;

    /**
     * @return string
     */
    public function getLastName(): string;

    /**
     * @param string $twitterId
     * @return MemberInterface
     */
    public function setTwitterID(string $twitterId): self;

    /**
     * @param $twitterUsername
     * @return $this
     */
    public function setTwitterUsername(string $twitterUsername): self;

    /**
     * @return string
     */
    public function getTwitterUsername(): string;

    /**
     * @return string
     */
    public function getTwitterID(): ?string;

    /**
     * @param string $fullName
     * @return MemberInterface
     */
    public function setFullName(string $fullName): self;

    /**
     * @return string
     */
    public function getFullName(): string;

    /**
     * @param bool $protected
     * @return MemberInterface
     */
    public function setProtected(bool $protected): self;

    /**
     * @return boolean
     */
    public function isProtected(): bool;

    /**
     * @return boolean
     */
    public function isNotProtected(): bool;

    /**
     * @param bool $suspended
     * @return MemberInterface
     */
    public function setSuspended(bool $suspended): self;

    /**
     * @return bool
     */
    public function isSuspended(): bool;

    /**
     * @return boolean
     */
    public function isNotSuspended(): bool;

    /**
     * @param $notFound
     * @return MemberInterface
     */
    public function setNotFound(bool $notFound): self;

    /**
     * @return boolean
     */
    public function hasBeenDeclaredAsNotFound(): bool;

    /**
     * @return bool
     */
    public function hasNotBeenDeclaredAsNotFound(): bool;

    /**
     * @return bool
     */
    public function isAWhisperer(): bool;
}
