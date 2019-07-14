<?php

namespace WeavingTheWeb\Bundle\UserBundle\Entity;

use Symfony\Component\Security\Core\Role\RoleInterface as BaseInterface;

/**
 * @package WeavingTheWeb\Bundle\UserBundle\Entity
 */
interface RoleInterface extends BaseInterface
{
    const ROLE_USER = 'user';

    const ROLE_ADMIN = 'admin';

    const ROLE_SUPER_ADMIN = 'super_admin';
}