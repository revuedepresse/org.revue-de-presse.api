<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Tests\Twitter;

use WeavingTheWeb\Bundle\AmqpBundle\Twitter\UserStatus;

use WeavingTheWeb\Bundle\FrameworkExtraBundle\Test\TestCase;

use WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException,
    WeavingTheWeb\Bundle\TwitterBundle\Api\TwitterErrorAwareInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @group   isolated-testing
 * @group   twitter-user-status
 */
class UserStatusTest extends TestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client $client
     */
    protected $client;

    /**
     * @group cli-twitter
     * @group messaging-twitter
     * @group twitter-api-error
     * @group twitter
     */
    public function testExecute()
    {
        $this->client = $this->getClient();

        $mockerBuilder = $this->getMockBuilder('\PhpAmqpLib\Message\AmqpMessage');
        $mock = $mockerBuilder->getMock();
        $mock->body = serialize(
            json_encode([
                'token' => '__tok__',
                'secret' => 'sha1of....',
                'screen_name' => 'fabpot'
            ])
        );

        $userStatusConsumer = new UserStatus();
        $serializerMock = $this->getSerializerMock();
        $userStatusConsumer->setSerializer($serializerMock);
        $userStatusConsumer->execute($mock);

        $exception = new SuspendedAccountException('This user has been suspended', TwitterErrorAwareInterface::ERROR_SUSPENDED_USER);
        $serializerMock = $this->getSerializerMock($exception);
        $userStatusConsumer->setSerializer($serializerMock);
        $userStatusConsumer->setLogger($this->get('logger'));
        $success = $userStatusConsumer->execute($mock);

        $this->assertEquals(true, $success, 'Messages associated to suspended user account should be successfully consumed.');
    }

    /**
     * @param null $exception
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSerializerMock($exception = null)
    {
        $mockBuilder = $this->getMockBuilder('\WeavingTheWeb\Bundle\TwitterBundle\Serializer\UserStatus')
            ->disableOriginalConstructor()
            ->disableAutoload();
        $mock = $mockBuilder->setMethods(['serialize', 'setupAccessor'])->getMock();

        if (is_null($exception)) {
            $mock->expects($this->once())->method('serialize')->will($this->returnValue(null));
        } else {
            $mock->expects($this->once())->method('serialize')->will(
                $this->throwException($exception)
            );
        }

        $mock->expects($this->once())->method('setupAccessor')->will($this->returnValue(null));

        return $mock;
    }
}
