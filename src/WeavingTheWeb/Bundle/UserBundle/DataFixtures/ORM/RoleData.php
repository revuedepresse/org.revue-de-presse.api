<?php

namespace WeavingTheWeb\Bundle\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture,
    Doctrine\Common\DataFixtures\OrderedFixtureInterface,
    Doctrine\Common\Persistence\ObjectManager;
use WeavingTheWeb\Bundle\UserBundle\Entity\Role;

class RoleData extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 100;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $rolesProperties = [
            [
                'name' => 'Weaver',
                'role' => 'ROLE_USER',
            ], [
                'name' => 'Super Weaver',
                'role' => 'ROLE_ADMIN',
            ], [
                'name' => 'Über Weaver',
                'role' => 'ROLE_SUPER_ADMIN',
            ]
        ];

        foreach ($rolesProperties as $roleProperties) {
            $role = new Role();

            $role->setName($roleProperties['name']);
            $role->setRole($roleProperties['role']);

            $this->addReference(strtolower($roleProperties['role']), $role);

            $manager->persist($role);
        }

        $manager->flush();
    }
}
