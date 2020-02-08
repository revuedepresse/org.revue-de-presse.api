<?php

namespace App\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture,
    Doctrine\Common\DataFixtures\FixtureInterface,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use App\Api\Entity\TokenType;

class TokenTypeData extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 250;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $tokenType = new TokenType();
        $tokenType->setName(TokenType::APPLICATION);
        $manager->persist($tokenType);

        $this->addReference('token_type_' . TokenType::APPLICATION, $tokenType);

        $tokenType = new TokenType();
        $tokenType->setName(TokenType::USER);
        $manager->persist($tokenType);

        $this->addReference('token_type_' . TokenType::USER, $tokenType);

        $manager->flush();
    }
}
