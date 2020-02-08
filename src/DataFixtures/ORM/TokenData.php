<?php

namespace App\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture,
    Doctrine\Common\DataFixtures\FixtureInterface,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Token,
    WeavingTheWeb\Bundle\ApiBundle\Entity\TokenType;

class TokenData extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 280;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $properties = [
            'oauthToken' => 'access token',
            'oauthTokenSecret' => '__secret',
            'frozenUntil' => null,
            'frozen' => false
        ];

        $token = new Token();

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\TokenType $userTokenType */
        $userTokenType = $manager->merge($this->getReference('token_type_' . TokenType::USER));
        $token->setType($userTokenType);

        $token->setOauthToken($properties['oauthToken']);
        $token->setOauthTokenSecret($properties['oauthTokenSecret']);
        $token->setFrozenUntil($properties['frozenUntil']);
        $token->setFrozen($properties['frozen']);

        $token->setCreatedAt(new \DateTime());
        $token->setUpdatedAt(new \DateTime());

        $this->addReference('user_token_1', $token);

        $manager->persist($token);

        $secondToken = new Token();
        $secondToken->setOauthToken('oauth_token_2');
        $secondToken->setOauthTokenSecret($properties['oauthTokenSecret']);
        $secondToken->setFrozenUntil($properties['frozenUntil']);
        $secondToken->setFrozen($properties['frozen']);
        $this->addReference('user_token_2', $secondToken);

        $secondToken->setCreatedAt(new \DateTime());
        $secondToken->setUpdatedAt(new \DateTime());
        
        $manager->persist($secondToken);

        $manager->flush();
    }
}
