<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Tests\Command;

use WeavingTheWeb\Bundle\FrameworkExtraBundle\Test\CommandTestCase;
use Prophecy\Prophet;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @group   isolated-testing
 * @group   messaging-twitter
 * @group   produce-list-messages
 * @group   twitter-lists
 */
class ProduceListsMembersCommandTest extends CommandTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client $client
     */
    protected $client;

    /**
     * @var \Prophecy\Prophet $prophet
     */
    protected $prophet;

    public function requireSQLiteFixtures()
    {
        return true;
    }

    public function setup()
    {
        $this->prophet = new Prophet;
    }

    public function testExecute()
    {
        $this->client = $this->getClient();

        $mockedProducer = $this->prophet->prophesize('\OldSound\RabbitMqBundle\RabbitMq\Producer');

        $container = $this->client->getContainer();

        $container->set('old_sound_rabbit_mq.weaving_the_web_amqp.twitter.user_status_producer', $mockedProducer);

        $this->commandClass = $this->getParameter('weaving_the_web_amqp.produce_lists_members_command.class');
        $this->setUpApplicationCommand();

        $this->commandTester = $this->getCommandTester('wtw:amqp:tw:prd:lm');
        $options = [
            'command' => $this->getCommandName(),
            '--screen_name' => 'Firefox',
            '--list' => 'Type',
        ];

        $this->commandTester->execute($options);
        $commandDisplay = $this->commandTester->getDisplay();

        $this->assertNotContains('Exception', $commandDisplay);
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();
    }
}
