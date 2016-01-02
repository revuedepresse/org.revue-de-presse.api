<?php

namespace WeavingTheWeb\Bundle\UserBundle\Tests\Security\Core\User;

use Doctrine\ORM\Mapping as ORM;

use WeavingTheWeb\Bundle\UserBundle\Entity\Role;

use WTW\UserBundle\Entity\User as BaseUser;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @ORM\Entity
 * @ORM\MappedSuperclass
 */
class User extends BaseUser
{
    /**
     * @param $username
     * @param $password
     * @param array $roles
     * @param bool|true $enabled
     * @param bool|true $userNonExpired
     * @param bool|true $credentialsNonExpired
     * @param bool|true $userNonLocked
     */
    public function __construct(
        $username,
        $password,
        array $roles = array(),
        $enabled = true,
        $userNonExpired = true,
        $credentialsNonExpired = true,
        $userNonLocked = true
    ) {
        parent::__construct();

        if (empty($username)) {
            throw new \InvalidArgumentException('The username cannot be empty.');
        }

        $this->accountNonExpired = $userNonExpired;
        $this->accountNonLocked = $userNonLocked;
        $this->credentialsNonExpired = $credentialsNonExpired;
        $this->enabled = $enabled;
        $this->locked = false;
        $this->password = $password;
        $this->positionInHierarchy = 0;
        $this->salt = null;
        $this->username = $username;

        if (count($roles) > 0) {
            foreach ($roles as $userRole) {
                if (is_string($userRole)) {
                    $role = new Role();
                    $role->setRole($userRole);
                    $role->setName($userRole);
                    $roleCandidate = $role;
                } else {
                    $roleCandidate = $userRole;
                }

                if (!$this->roles->contains($roleCandidate)) {
                    $this->roles->add($roleCandidate);
                }
            }
        }
    }
}
