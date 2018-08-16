<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Tests\Twitter;

use Prophecy\Argument,
    Prophecy\Prophet;

use WTW\CodeGeneration\QualityAssuranceBundle\Test\TestCase;

use WeavingTheWeb\Bundle\AmqpBundle\Twitter\UserStatus;

use WeavingTheWeb\Bundle\TwitterBundle\Api\TwitterErrorAwareInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException;

/**
 * @package WeavingTheWeb\Bundle\AmqpBundle\Tests\Twitter
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UserStatusTest extends TestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client $client
     */
    protected $client;

    /**
     * @var \Prophecy\Prophet
     */
    protected $prophet;

    /**
     * @var \WeavingTheWeb\Bundle\AmqpBundle\Twitter\UserStatus
     */
    private $userStatusConsumer;

    public function setUp()
    {
        parent::setUp();

        $this->client = $this->getClient();
        $this->userStatusConsumer = new UserStatus();

        $this->prophet = new Prophet();
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();

        parent::tearDown();
    }

    /**
     * @group cli-twitter
     * @group messaging-twitter
     * @group twitter-api-error
     * @group twitter
     */
    public function testExecute()
    {
        $mockerBuilder = $this->getMockBuilder('\PhpAmqpLib\Message\AmqpMessage');
        $mock = $mockerBuilder->getMock();
        $mock->body = serialize(
            json_encode([
                'token' => '__tok__',
                'secret' => 'sha1of....',
                'screen_name' => 'fabpot'
            ])
        );

        $this->userStatusConsumer->setSerializer($this->getSerializerMock());
        $success = $this->userStatusConsumer->execute($mock);
        $this->assertTrue($success, 'It should serialize statuses.');

        $this->userStatusConsumer->setSerializer($this->getSerializerMock());
        $success = $this->userStatusConsumer->execute($mock);
        $this->assertTrue($success, 'It should skip whisperers');

        $exception = new SuspendedAccountException(
            'This user has been suspended',
            TwitterErrorAwareInterface::ERROR_SUSPENDED_USER
        );
        $this->userStatusConsumer->setSerializer($this->getSerializerMock($exception));
        $this->userStatusConsumer->setLogger($this->get('logger'));
        $success = $this->userStatusConsumer->execute($mock);

        $this->assertTrue($success, 'Messages associated to suspended user account should be successfully consumed.');
    }

    /**
     * @param null $exception
     * @param bool $expectSerialization
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSerializerMock($exception = null, $expectSerialization = true)
    {
        $mockBuilder = $this->getMockBuilder('\WeavingTheWeb\Bundle\TwitterBundle\Serializer\UserStatus')
            ->disableOriginalConstructor()
            ->disableAutoload();

        $methods = ['setupAccessor'];
        if ($expectSerialization) {
            array_push($methods, 'serialize');
        }

        $mock = $mockBuilder->setMethods($methods)->getMock();

        if (is_null($exception)) {
            if ($expectSerialization) {
                $mock->expects($this->once())->method('serialize')->will($this->returnValue(true));
            }
        } else {
            $mock->expects($this->once())->method('serialize')->will(
                $this->throwException($exception)
            );
        }

        $mock->expects($this->once())->method('setupAccessor')->will($this->returnValue(null));

        return $mock;
    }

    /**
     * @param $returnValue
     * @return object
     */
    protected function mockWhispererRepository($returnValue)
    {
        $whispererRepositoryMock = $this->prophet->prophesize(
            '\WeavingTheWeb\Bundle\ApiBundle\Repository\WhispererRepository'
        );
        $whispererRepositoryMock->findOneBy(Argument::type('array'))->willReturn($returnValue);

        return $whispererRepositoryMock->reveal();
    }

    /**
     * @test
     * @group wip
     */
    public function it_should_persist_not_found_users()
    {
        $userRepositoryProphecy = $this->mockUserRepository();
        $userRepositoryMock = $userRepositoryProphecy->reveal();
        $this->userStatusConsumer->setUserRepository($userRepositoryMock);

        $notFoundUser = 'not_found_user';

        $caughtExceptions = [];
        try {
            $this->userStatusConsumer->handleNotFoundUsers($notFoundUser);
        } catch (\Exception $exception) {
            $caughtExceptions[] = $exception;
        } finally {
            $this->assertEquals(1, count($caughtExceptions), 'It should raise an exception');
            $this->assertEquals(
                UserStatus::ERROR_CODE_USER_NOT_FOUND,
                $caughtExceptions[0]->getCode(),
                'It should raise an exception with the exception code');
        }

        $userRepositoryProphecy = $this->mockUserRepository($mockUser = true);
        $userRepositoryMock = $userRepositoryProphecy->reveal();
        $this->userStatusConsumer->setUserRepository($userRepositoryMock);

        $user = $this->userStatusConsumer->handleNotFoundUsers($notFoundUser);
        $this->assertInternalType('object', $user,
            'It should return a user which can not be found anymore');
    }

    /**
     * @param bool $mockUser
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function mockUserRepository($mockUser = false)
    {
        $userRepositoryProphecy = $this->prophet->prophesize('\WTW\UserBundle\Repository\UserRepository');

        if ($mockUser) {
            $userProphecy = $this->prophet->prophesize('\WTW\UserBundle\Entity\User');
            $userMock = $userProphecy->reveal();

            $userRepositoryProphecy->findOneBy(Argument::any())
                ->willReturn($userMock);
            $userRepositoryProphecy->declareUserAsNotFound(Argument::any())
                ->willReturn($userMock);
        }

        return $userRepositoryProphecy;
    }
}
