<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Command\Tests;

use Prophecy\Argument,
    Prophecy\Prophet;

use WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException;

use WeavingTheWeb\Bundle\FrameworkExtraBundle\Test\CommandTestCase;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @group   cli-twitter
 * @group   isolated-testing
 * @group   produce-user-messages
 */
class ProduceUserFriendlistCommandTest extends CommandTestCase
{
    /** @var \Symfony\Bundle\FrameworkBundle\Client $client */
    protected $client;

    /**
     * @var \Prophecy\Prophet $prophet
     */
    protected $prophet;

    public function requireSQLiteFixtures()
    {
        return true;
    }

    public function setUp()
    {
        $this->prophet = new Prophet();
    }

    /**
     * @param $options
     * @param $producedMessages
     * @param $mockingOptions
     * @param \Exception $expectedException
     * @param string $expectedOutput
     *
     * @group cli-twitter
     * @group messaging-twitter
     * @group twitter
     * @group twitter-api-error
     * @dataProvider getOptions
     */
    public function testExecute(
        $options,
        $producedMessages,
        $mockingOptions,
        \Exception $expectedException = null,
        $expectedOutput = 'amqp.production.friendlist.success'
    )
    {
        $this->client = $this->getClient();

        if (array_key_exists('accessor', $mockingOptions)) {
            /** @var \WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor $accessorMock */
            $accessorMock = $this->prophet->prophesize('\WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor');
            $accessorMock->setUserToken(Argument::type('string'))->willReturn(null);
            $accessorMock->setUserSecret(Argument::type('string'))->willReturn(null);
            $accessorMock->showUserFriends(Argument::type('string'))->willReturn(
                (object)[
                    'ids' => ['friend']
                ]
            );

            if (array_key_exists('exception', $mockingOptions['accessor'])) {
                $exception = $mockingOptions['accessor']['exception'];
            } else {
                $exception = '\WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException';
            }

            $accessorMock->showUser(Argument::type('string'))
                ->willThrow($exception);

            $container = $this->getContainer();
            $container->set('weaving_the_web_twitter.api_accessor', $accessorMock->reveal());
        } else {
            $this->setupAccessorMock();
            $this->setupUserRepositoryMock($mockingOptions);
        }
        $this->setupProduceMock();

        $this->commandClass = $this->getParameter('weaving_the_web_amqp.produce_user_friendlist_command.class');
        $this->setUpApplicationCommand();

        $this->commandTester = $this->getCommandTester('wtw:amqp:tw:prd:utl');

        $tokens = $this->extractOauthTokens($options);

        $options = [
            'command' => $this->getCommandName(),
            '--screen_name' => $options['screen_name'],
            '--oauth_token' => $tokens['token'],
            '--oauth_secret' => $tokens['secret'],
        ];

        $this->commandTester->execute($options);
        $commandDisplay = $this->commandTester->getDisplay();

        /** @var \Symfony\Component\Translation\Translator $translator */
        $translator = $this->get('translator');

        $successMessage = $translator->trans($expectedOutput,
            [
                '{{ count }}' => $producedMessages,
                '{{ user }}' => 'friend'
            ]
        );
        $this->assertContains($successMessage, $commandDisplay);

        if (!is_null($expectedException)) {
            try {
                $this->commandTester->execute($options);
            } catch (\Exception $exception) {
                $this->assertInstanceOf(get_class($expectedException), $exception);

                if ($exception instanceof SuspendedAccountException) {
                    $this->assertEquals($expectedException->getMessage(), $exception->getMessage());
                }
            }
        }
    }

    public function getOptions()
    {
        return [
            [
               'options' => [
                   'screen_name' => 'weaver',
               ],
               'produced_messages' => 0,
               'mocking_options' => [
                   'accessor' => [],
               ],
               'expected_exception' => null,
               'expected_output' => 'amqp.output.unavailable_resource',
            ], [
               'options' => [
                   'screen_name' => 'weaver',
               ],
               'produced_messages' => 0,
               'mocking_options' => [
                   'accessor' => [
                       'exception' => new SuspendedAccountException('User has been suspended', 63)
                   ],
               ],
               'expected_exception' => null,
               'expected_output' => 'amqp.output.suspended_account',
            ], [
               'options' => [
                   'screen_name' => 'Firefox',
               ],
               'produced_messages' => 2,
               'mocking_options' => [
                   'user_repository' => [
                       'find_one_by' => [
                           'calls' => 3,
                       ],
                   ],
               ],
               'expected_exception' => new \Exception(),
            ], [
                'options' => [
                    'screen_name' => 'shal',
                    'oauth_token' => 'weaving_the_web_twitter.oauth_token.default',
                    'oauth_secret' => 'weaving_the_web_twitter.oauth_secret.default'
                ],
                'produced_messages' => 2,
                'mocking_options' => [
                    'user_repository' => [],
                ],
            ], [
                'options' => [
                    'screen_name' => 'MathieuCoste',
                ],
                'produced_messages' => 2,
                'mocking_options' => [
                    'user_repository' => [],
                ],
            ],
        ];
    }

    public function setupProduceMock()
    {
        $mockBuilder = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\Produce')
            ->disableOriginalConstructor()
            ->disableAutoload();
        $mock = $mockBuilder->setMethods(['publish', 'setContentType'])->getMock();
        $mock->expects($this->any())->method('publish')->will($this->returnValue(null));
        $mock->expects($this->any())->method('setContentType')->will($this->returnValue(null));

        $this->client->getKernel()->getContainer()
            ->set('old_sound_rabbit_mq.weaving_the_web_amqp.twitter.user_status_producer', $mock);
    }

    public function setupAccessorMock()
    {
        $mockBuilder = $this->getMockBuilder('\WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor')
            ->disableOriginalConstructor()
            ->disableAutoload();
        $mock = $mockBuilder->setMethods(['setUserToken', 'setUserSecret', 'showUser', 'showUserFriends'])->getMock();
        $mock->expects($this->any())->method('showUserFriends')->will($this->returnValue((object)['ids' => ['user1', 'user2']]));
        $mock->expects($this->any())->method('showUser')->will($this->returnCallback(function ($input) {
            return (object)['screen_name' => $input];
        }));
        $mock->expects($this->any())->method('setUserToken')->will($this->returnValue(null));
        $mock->expects($this->any())->method('setUserSecret')->will($this->returnValue(null));

        $this->client->getKernel()->getContainer()
            ->set('weaving_the_web_twitter.api_accessor', $mock);
    }

    /**
     * @param $options
     */
    public function setupUserRepositoryMock($options)
    {
        if (array_key_exists('user_repository', $options)) {
            if (!array_key_exists('find_one_by', $options['user_repository'])) {
                $calls = 2;
                $callable = array($this, 'findOneBy');
            } else {
                $calls = $options['user_repository']['find_one_by']['calls'];
                $callable = array($this, 'findOneByBis');
            }

            $mockBuilder = $this->getMockBuilder('WTW\UserBundle\Repository\UserRepository')
                ->disableOriginalConstructor()
                ->disableAutoload();
            $mock = $mockBuilder->setMethods(['findOneBy'])->getMock();
            $mock->expects($this->exactly($calls))->method('findOneBy')->will($this->returnCallback($callable));

            $this->client->getKernel()->getContainer()
                ->set('wtw_user.repository.user', $mock);
        }
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function findOneBy()
    {
        return $this->getUserMock();
    }

    /**
     * @param $criteria
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function findOneByBis($criteria)
    {
        if ($criteria['twitterID'] === 'user2') {
            return $this->getUserMock();
        }
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getUserMock()
    {
        $userMockBuilder = $this->getMockBuilder('WTW\UserBundle\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->disableAutoload();
        $userMock = $userMockBuilder->setMethods(['getTwitterUsername'])->getMock();
        $userMock->expects($this->exactly(1))->method('getTwitterUsername')->will(
            $this->returnCallback(
                function () {
                    return 'user1';
                }
            )
        );

        return $userMock;
    }

    /**
     * @param $options
     * @return array
     */
    protected function extractOauthTokens($options)
    {
        if (array_key_exists('oauth_token', $options)) {
            $oauthToken = $this->getParameter($options['oauth_token']);
        } else {
            $oauthToken = null;
        }
        if (array_key_exists('oauth_secret', $options)) {
            $oauthSecret = $this->getParameter($options['oauth_secret']);
        } else {
            $oauthSecret = null;
        }

        return array('token' => $oauthToken, 'secret' => $oauthSecret);
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();
    }
}
