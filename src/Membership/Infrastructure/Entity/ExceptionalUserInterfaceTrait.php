<?php

namespace App\Membership\Infrastructure\Entity;

use App\Membership\Domain\Model\MemberInterface;

trait ExceptionalUserInterfaceTrait
{
    private $exception;

    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials()
    {
    }

    public function getPassword(): string
    {
        return '';
    }

    public function getRoles(): array
    {
        return [];
    }

    public function getSalt(): string
    {
        return '';
    }

    public function getUrl(): ?string
    {
        return '';
    }

    public function setException(\Exception $exception): \Exception
    {
        $this->exception = $exception;

        return $this->exception;
    }

    public function setUsername(string $username): MemberInterface
    {
        return $this;
    }

    public function setEmail(string $email): MemberInterface
    {
        return $this;
    }
}
