<?php

namespace App\Membership\Domain\Entity;

trait ExceptionalUserInterfaceTrait
{
    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials()
    {
    }

    /**
     * @return string
     */
    public function getUrl(): ?string {
        return '';
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return '';
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
}
