<?php

namespace WeavingTheWeb\Bundle\UserBundle\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Model\UserInterface,
    FOS\UserBundle\Util\CanonicalizerInterface,
    FOS\UserBundle\Doctrine\UserManager as BaseUserManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserManager extends BaseUserManager
{
    /**
     * Updates a user.
     *
     * @param UserInterface $user
     * @param Boolean       $andFlush Whether to flush the changes (default true)
     */
    public function updateUser(UserInterface $user, $andFlush = true)
    {
        $this->updateCanonicalFields($user);
        $this->updatePassword($user);
        $this->updateRoles($user);

        $this->objectManager->persist($user);
        if ($andFlush) {
            $this->objectManager->flush();
        }
    }

    /**
     *
     */
    public function updateRoles(UserInterface $user)
    {
        $roles = $user->getRoles();
        $roleRepository = $this->objectManager->getRepository('WeavingTheWebUserBundle:Role');

        foreach ($roles as $role) {
            $roleName = (string)$role;
            $roleEntity = $roleRepository->findOneByRole($roleName);

            $user->removeRole($role);
            $user->addRole($roleEntity);
        }
    }
}
