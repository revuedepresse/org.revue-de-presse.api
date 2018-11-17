<?php

namespace WTW\UserBundle\Tests\Security\Core\User;

use Doctrine\ORM\Mapping as ORM;
use WTW\UserBundle\Entity\User as BaseUser;

/**
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
        $this->enabled = $enabled;
        $this->positionInHierarchy = 0;
    }
}
