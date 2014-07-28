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
class HeaderData extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 300;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $header = new Header();
        $header->setHdrImapUid(1);
        $header->setDate(new \DateTime());
        $header->setHdrValue('Ping: Pong');
        $header->setParsed(false);
        $header->setLabelled(false);

        $manager->persist($header);
        $manager->flush();

        $this->addReference('header', $header);
    }
}