<?php
declare(strict_types=1);

namespace App\Tests\Amqp\Command;

use App\Amqp\Command\FetchMemberStatusMessageDispatcher;
use App\Api\Entity\Token;
use App\Api\Repository\TokenRepository;
use App\Membership\Entity\Member;
use App\Membership\Entity\MemberInterface;
use App\Membership\Repository\MemberRepository;
use App\Twitter\Api\Accessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group command
 */
class FetchMemberStatusMessageDispatcherTest extends KernelTestCase
{
    private const SCREEN_NAME = 'BobEponge';

    private const LIST_ID   = 1;
    private const LIST_NAME = 'science';

    private const MEMBER_ID          = '1';
    private const MEMBER_NAME        = 'Marie Curie';
    private const MEMBER_SCREEN_NAME = 'mariec';
    private const MEMBER_EMAIL       = 'marie@curie.physics';

    private const USER_TOKEN  = 'user-token';
    private const USER_SECRET = 'user-secret';

    private const USER_TOKEN_SECONDARY  = 'user-token-secondary';
    private const USER_SECRET_SECONDARY = 'user-secret-secondary';

    private const CONSUMER_KEY    = 'consumer-key';
    private const CONSUMER_SECRET = 'consumer-secret';

    /**
     * @var Command
     */
    private Command $command;

    /**
     * @var CommandTester
     */
    private CommandTester $commandTester;

    /**
     * @test
     */
    public function it_should_dispatch_messages_to_fetch_member_statuses(): void
    {
        $this->commandTester->execute(
            [
                '--list'        => self::LIST_NAME,
                '--screen_name' => self::SCREEN_NAME,
            ],
            ['capture_stderr_separately' => true]
        );

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The status code of a command should be successful',
            );
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        $this->removeTargetMember(self::$container->get('doctrine.orm.entity_manager'));

        $command = self::$container->get(FetchMemberStatusMessageDispatcher::class);
        $command->setTokenRepository($this->prophesizeTokenRepository());
        $command->setAccessor($this->prophesizeAccessor());
        $command->setMemberRepository($this->prophesizeMemberRepository());

        $application = new Application($kernel);

        $this->command = $application->find('press-review:dispatch-messages-to-fetch-member-statuses');

        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $this->removeTargetMember($entityManager);
    }

    /**
     * @return Accessor
     * @throws
     */
    private function prophesizeAccessor()
    {
        /** @var Accessor $accessorProphecy */
        $accessorProphecy = $this->prophesize(Accessor::class);
        $accessorProphecy
            ->getUserOwnerships(self::SCREEN_NAME)
            ->willReturn(
                (object) [
                    'lists' => [
                        self::LIST_NAME => (object) [
                            'name'   => self::LIST_NAME,
                            'id'     => self::LIST_ID,
                            'id_str' => (string) self::LIST_ID,
                        ]
                    ]
                ]
            );

        $accessorProphecy
            ->setUserToken(self::USER_TOKEN)
            ->willReturn();

        $accessorProphecy
            ->setUserSecret(self::USER_SECRET)
            ->willReturn();

        $accessorProphecy
            ->setUserToken(self::USER_TOKEN_SECONDARY)
            ->willReturn();

        $accessorProphecy
            ->setUserSecret(self::USER_SECRET_SECONDARY)
            ->willReturn();

        $accessorProphecy
            ->setConsumerKey(self::CONSUMER_KEY)
            ->willReturn();

        $accessorProphecy
            ->setConsumerSecret(self::CONSUMER_SECRET)
            ->willReturn();

        $members = (object) [
            'users' => [
                (object) [
                    'name'        => self::MEMBER_NAME,
                    'id'          => self::MEMBER_ID,
                    'screen_name' => self::MEMBER_SCREEN_NAME
                ]
            ]
        ];

        $accessorProphecy
            ->getListMembers(self::LIST_ID)
            ->willReturn($members);

        $accessorProphecy
            ->showUser(self::MEMBER_SCREEN_NAME)
            ->willReturn(
                (object) [
                    'screen_name' => self::MEMBER_SCREEN_NAME
                ]
            );

        return $accessorProphecy->reveal();
    }

    /**
     * @return TokenRepository
     */
    private function prophesizeMemberRepository()
    {
        /** @var MemberRepository $memberRepositoryProphecy */
        $memberRepositoryProphecy = $this->prophesize(MemberRepository::class);
        $memberRepositoryProphecy
            ->findOneBy(['twitterID' => self::MEMBER_ID])
            ->willReturn(null);

        $member = new Member();
        $member->setTwitterID(self::MEMBER_ID);
        $member->setFullName(self::MEMBER_NAME);
        $member->setTwitterUsername(self::MEMBER_SCREEN_NAME);
        $member->setProtected(false);
        $member->setSuspended(false);
        $member->setEmail(self::MEMBER_EMAIL);

        $memberRepositoryProphecy
            ->make(
                self::MEMBER_ID,
                self::MEMBER_SCREEN_NAME,
                false,
                false
            )
            ->willReturn($member);

        return $memberRepositoryProphecy->reveal();
    }

    /**
     * @return TokenRepository
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function prophesizeTokenRepository()
    {
        /** @var TokenRepository $tokenRepositoryProphecy */
        $tokenRepositoryProphecy = $this->prophesize(TokenRepository::class);
        $tokenRepositoryProphecy
            ->howManyUnfrozenTokenAreThere()
            ->willReturn(1);

        $token = new Token;
        $token->setOauthToken(self::USER_TOKEN_SECONDARY);
        $token->setOauthTokenSecret(self::USER_SECRET_SECONDARY);
        $token->consumerKey    = self::CONSUMER_KEY;
        $token->consumerSecret = self::CONSUMER_SECRET;

        $tokenRepositoryProphecy->findTokenOtherThan(self::USER_TOKEN)
                                ->willReturn($token);

        return $tokenRepositoryProphecy->reveal();
    }

    /**
     * @param EntityManagerInterface $entityManager
     */
    private function removeTargetMember(EntityManagerInterface $entityManager): void
    {
        $memberRepository = $entityManager
            ->getRepository('Membership:Member');

        $member = $memberRepository->findOneBy(['twitterID' => self::MEMBER_ID]);

        if ($member instanceof MemberInterface) {
            $entityManager->remove($member);
            $entityManager->flush();
        }
    }
}
