<?php

namespace WTW\UserBundle\Tests\Security\Core\User;

use Doctrine\ORM\Mapping as ORM;
use WTW\UserBundle\Entity\User as BaseUser;

/**
 * Class User
 * @package WTW\UserBundle\Tests\Security\Core\User
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @ORM\Entity
 * @ORM\MappedSuperclass
 */
class User extends BaseUser
{
    public function __construct(
        $username,
        $password,
        array $roles = array(),
        $enabled = true,
        $userNonExpired = true,
        $credentialsNonExpired = true,
        $userNonLocked = true
    ) {
        if (empty($username)) {
            throw new \InvalidArgumentException('The username cannot be empty.');
        }

        $this->username = $username;
        $this->password = $password;
        $this->enabled = $enabled;
        $this->locked = false;
        $this->accountNonExpired = $userNonExpired;
        $this->credentialsNonExpired = $credentialsNonExpired;
        $this->accountNonLocked = $userNonLocked;
        $this->positionInHierarchy = 0;
        $this->roles = $roles;
    }
}
