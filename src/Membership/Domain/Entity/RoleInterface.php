<?php

namespace App\Membership\Domain\Entity;

/**
 * @package WeavingTheWeb\Bundle\UserBundle\Entity
 */
interface RoleInterface
{
    const ROLE_USER = 'user';

    const ROLE_ADMIN = 'admin';

    const ROLE_SUPER_ADMIN = 'super_admin';
}