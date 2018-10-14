<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Tests\Command;

use Prophecy\Argument,
    Prophecy\Prophet;
use Symfony\Component\Console\Output\OutputInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException;
use WTW\CodeGeneration\QualityAssuranceBundle\Test\CommandTestCase;

/**
 * @group  serialize-status-command
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SerializeStatusesCommandTest extends CommandTestCase
{
    /** @var \Prophecy\Prophet */
    protected $prophet;

    public function setup() {
        $this->prophet = new Prophet();
    }

    public function requiredFixtures()
    {
        return true;
    }

    /**
     * @group requires-internet
     * @group cli-twitter
     * @group messaging-twitter
     *
     * @dataProvider getOptions
     *
     * @param $options
     * @throws \Exception
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    public function testExecute($options)
    {
        $this->client = $this->getClient();

        $user = (object) [
            'profile_image_url' => 'http://image.net',
            'screen_name' => $options['user']['screen_name'],
            'name' => $options['user']['screen_name'],
        ];

        /** @var \WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor $accessorMock */
        $accessorMock = $this->prophet->prophesize('\WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor');

        if (array_key_exists('protected', $options['user'])) {
            $user->protected = $options['user']['protected'];

            $expectedCommandReturnCode = 1;
            $expectedSavedStatuses = 0;

            $accessorMock->showUser(Argument::type('string'))->willThrow(new ProtectedAccountException());
        } else {
            $user->protected = false;
            $user->statuses_count = $options['user']['statuses_count'];

            $expectedCommandReturnCode = 0;
            $expectedSavedStatuses = $options['user']['statuses_count'];

            $accessorMock->showUser(Argument::type('string'))->willReturn($user);
        }

        $accessorMock->isApiRateLimitReached(Argument::type('string'))->willReturn(false);
        $accessorMock->setAuthenticationHeader(Argument::type('string'))->willReturn(null);

        $statusText = 'status content';
        $accessorMock->fetchStatuses(Argument::type('array'))->willReturn(
            [
                (object)[
                    'hash' => sha1(1),
                    'text' => $statusText,
                    'api_document' => '{}',
                    'created_at' => null,
                    'id_str' => 1,
                    'user' => $user
                ]
            ]
        );

        $container = $this->client->getKernel()->getContainer();
        /** @var \WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor $accessorMock */
        $container->set('weaving_the_web_twitter.api_accessor', $accessorMock->reveal());

        $mockedTokenRepository = $this->getMockedTokenRepository();
        $serializer = $container->get('weaving_the_web_twitter.serializer.user_status');
        $serializer->setTokenRepository($mockedTokenRepository->reveal());

        $this->commandClass = $this->getParameter('weaving_the_web_twitter.serialize_statuses.class');
        $this->setUpApplication();

        $this->commandTester = $this->getCommandTester('wtw:tw:sts');

        $tokens = $this->extractOauthTokens($options);

        $commandOptions = [
            'command' => $this->getCommandName(),
            '--count' => 1,
            '--screen_name' => $options['user']['screen_name'],
            '--oauth_token' => $tokens['token'],
            '--oauth_secret' => $tokens['secret'],
        ];
        if (array_key_exists('bearer', $options)) {
            $commandOptions['--bearer'] = true;
            $accessorMock->setAuthenticationHeader(Argument::type('string'))->shouldBeCalled();
        }
        if (array_key_exists('greedy', $options)) {
            $commandOptions['--greedy'] = true;
            $accessorMock->setAuthenticationHeader(Argument::type('string'))->shouldNotBeCalled();
        }

        $returnCode = $this->commandTester->execute($commandOptions, ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);
        $this->assertEquals(
            $expectedCommandReturnCode,
            $returnCode,
            'The tested command should return 0 when it terminates successfully'
        );

        $commandDisplay = $this->commandTester->getDisplay();

        /** @var \Symfony\Component\Translation\Translator $translator */
        $translator = $this->get('translator');
        $successMessage = $translator->trans('twitter.success.statuses.persistence');

        $this->assertContains($successMessage, $commandDisplay);

        // Executes same command again to make sure persistence cannot happen twice for same status id / user identifier
        $this->commandTester->execute($commandOptions);

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository $userStreamRepository */
        $userStreamRepository = $this->get('weaving_the_web_twitter.repository.status');

        if (array_key_exists('oauth_token', $options)) {
            $expectedStatusIdentifier = $options['oauth_token'];
        } else {
            $expectedStatusIdentifier = $this->getParameter('api_twitter_user_token');
        }

        $results = $userStreamRepository->findBy(['identifier' => $expectedStatusIdentifier]);
        $this->assertCount(
            $expectedSavedStatuses,
            $results,
            '' . $expectedSavedStatuses . ' status should have been persisted for token "' . $expectedStatusIdentifier . '"'
        );

        if ($expectedSavedStatuses > 0) {
            /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Status $status */
            $status = $results[0];
            $this->assertEquals(
                $statusText,
                $status->getText(),
                'It should save status text to a database'
            );
            $this->assertNotNull(
                $status->getStatusId(),
                'It should save the original status id to a database'
            );
        }
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public function getMockedTokenRepository()
    {
        $tokenRepositoryClass = $this->getParameter('weaving_the_web_api.repository.token.class');

        $tokenMock = $this->prophet->prophesize('\WeavingTheWeb\Bundle\ApiBundle\Entity\Token');
        $tokenMock->getFrozenUntil()->willReturn(new \DateTime('1979'));
        $tokenMock->isFrozen()->willReturn(false);

        $tokenRepositoryMock = $this->prophet->prophesize($tokenRepositoryClass);

        $tokenRepositoryMock->findOneBy(Argument::any())->willReturn($tokenMock);
        $tokenRepositoryMock->freezeToken(Argument::any())->willReturn(null);
        $tokenRepositoryMock->refreshFreezeCondition(Argument::any(), Argument::cetera())->willReturn($tokenMock);
        $tokenRepositoryMock->findFirstUnfrozenToken()->willReturn(null);

        return $tokenRepositoryMock;
    }

    /**
     * @param $options
     * @return array
     */
    protected function extractOauthTokens($options)
    {
        if (array_key_exists('oauth_token', $options)) {
            $oauthToken = $options['oauth_token'];
        } else {
            $oauthToken = null;
        }
        if (array_key_exists('oauth_secret', $options)) {
            $oauthSecret = $options['oauth_secret'];
        } else {
            $oauthSecret = null;
        }

        return array('token' => $oauthToken, 'secret' => $oauthSecret);
    }

    public function getOptions()
    {
        return [
            [
                'options' =>  [
                    'user' => [
                        'screen_name' => 'weaver',
                        'statuses_count' => 1,
                    ],
                    'bearer' => true
                ]
            ], [
                'options' => [
                    'user' => [
                        'screen_name' => 'weaver',
                        'statuses_count' => 1,
                    ]
                ]
            ], [
                'options' => [
                    'user' => [
                        'screen_name' => 'weaver',
                        'statuses_count' => 1,
                    ],
                    'greedy' => true,
                    'oauth_token' => 'forced_token',
                    'oauth_secret' => 'forced_token_secret'
                ]
            ],
            [
                'options' => [
                    'user' => [
                        'screen_name' => 'weaver',
                        'protected' => true, // No tweets can be retrieved from protected accounts
                    ]
                ]
            ],
        ];
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();
    }
}
