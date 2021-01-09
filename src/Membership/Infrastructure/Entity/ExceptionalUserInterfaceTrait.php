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

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return '';
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getUrl(): ?string
    {
        return '';
    }

    /**
     * @param \Exception $exception
     *
     * @return \Exception
     */
    public function setException(\Exception $exception)
    {
        return $this->exception = $exception;
    }

    public function setUsername(string $username): MemberInterface
    {
        return $this;
    }

    public function setEmail(string $email): MemberInterface {
        return $this;
    }
}
