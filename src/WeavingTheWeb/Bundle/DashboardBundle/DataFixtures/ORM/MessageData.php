<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture,
    Doctrine\Common\DataFixtures\OrderedFixtureInterface,
    Doctrine\Common\Persistence\ObjectManager;

use WeavingTheWeb\Bundle\MailBundle\Entity\Header,
    WeavingTheWeb\Bundle\MailBundle\Entity\Message;

/**
 * @package WeavingTheWeb\Bundle\DashboardBundle\DataFixtures\ORM
 */
class MessageData extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 400;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $message = new Message();

        /** @var Header $header */
        $header = $manager->merge($this->getReference('header'));

        $message->setHdrId($header->getHdrId());
        $message->setHeader($header);
        $message->setIndexed(false);

        $manager->persist($message);

        $header->setMessage($message);
        $manager->persist($header);

        $manager->flush();
    }
}