<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Command;

use Prophecy\Argument;
use WTW\CodeGeneration\QualityAssuranceBundle\Test\CommandTestCase;
use Prophecy\Prophet;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @group   twitter-lists
 * @group   messaging-twitter
 */
class ProduceListMembersCommandTest extends CommandTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client $client
     */
    protected $client;

    /**
     * @var \Prophecy\Prophet $prophet
     */
    protected $prophet;

    public function requiredFixtures()
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

        $container = $this->client->getContainer();

        $mockedProducer = $this->prophet->prophesize('\OldSound\RabbitMqBundle\RabbitMq\Producer');
        $container->set('old_sound_rabbit_mq.weaving_the_web_amqp.twitter.user_status_producer', $mockedProducer);

        $mockedAccessor = $this->prophet->prophesize('\WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor');
        $lists = [
            (object) [
                'name' => 'Specific List',
                'id' => 1
            ]
        ];
        $mockedAccessor->getUserOwnerships(Argument::type('string'))->willReturn((object)['lists' => $lists]);
        $mockedAccessor->setUserToken(Argument::type('string'))->willReturn(null);
        $mockedAccessor->setUserSecret(Argument::type('string'))->willReturn(null);


        $members = [
            (object) [
                'id' => 1,
                'screen_name' => 'user'
            ]
        ];

        $mockedAccessor->getListMembers(Argument::type('integer'))->willReturn((object)['users' => $members]);


        $container->set('weaving_the_web_twitter.api_accessor', $mockedAccessor->reveal());

        $this->commandClass = $this->getParameter('weaving_the_web_amqp.produce_lists_members_command.class');
        $this->setUpApplication();

        $this->commandTester = $this->getCommandTester('wtw:amqp:tw:prd:lm');
        $options = [
            'command' => $this->getCommandName(),
            '--screen_name' => 'Firefox',
            '--list' => 'Specific List',
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
