<?php
declare(strict_types=1);

namespace App\Tests\Amqp\Command;

use App\Amqp\Command\FetchPublicationMessageDispatcher;
use App\Api\AccessToken\Repository\TokenRepository;
use App\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Api\AccessToken\TokenChangeInterface;
use App\Api\Entity\Token;
use App\Api\Entity\TokenInterface;
use App\Api\Exception\InvalidSerializedTokenException;
use App\Membership\Entity\Member;
use App\Membership\Entity\MemberInterface;
use App\Membership\Exception\InvalidMemberIdentifier;
use App\Membership\Repository\MemberRepository;
use App\Tests\Builder\ApiAccessorBuilder;
use App\Tests\Builder\TokenChangeBuilder;
use App\Tests\Builder\TokenRepositoryBuilder;
use App\Twitter\Api\Accessor;
use App\Twitter\Api\ApiAccessorInterface;
use App\Twitter\Api\Resource\OwnershipCollection;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group command
 */
class FetchPublicationMessageDispatcherTest extends KernelTestCase
{
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
    public function it_dispatches_messages_to_fetch_member_statuses(): void
    {
        // Act

        $this->commandTester->execute(
            [
                '--list'        => ApiAccessorBuilder::LIST_NAME,
                '--screen_name' => ApiAccessorBuilder::SCREEN_NAME,
            ]
        );

        // Assert

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The status code of a command should be successful',
            );
    }

    /**
     * @throws InvalidMemberIdentifier
     * @throws InvalidSerializedTokenException
     */
    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        $this->removeTargetMember(self::$container->get('doctrine.orm.entity_manager'));

        $command = self::$container->get(FetchPublicationMessageDispatcher::class);
        $command->setAccessor($this->prophesizeAccessor());
        $command->setMemberRepository($this->prophesizeMemberRepository());
        $command->setTokenChange($this->prophesizeTokenChange());

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
     * @return ApiAccessorInterface
     * @throws
     */
    private function prophesizeAccessor(): ApiAccessorInterface
    {
        /** @var Accessor $accessor */
        $accessorBuilder = ApiAccessorBuilder::newApiAccessorBuilder();

        $accessor = $accessorBuilder->willGetOwnershipCollectionForMember(
            $accessorBuilder->makeOwnershipCollection(),
            $accessorBuilder::SCREEN_NAME
        )
        ->willGetMembersInList(
            $accessorBuilder::LIST_ID,
            $accessorBuilder->makeMemberList()
        )
        ->willGetProfileForMemberHavingScreenName(
            (object) [
                'screen_name' => $accessorBuilder::MEMBER_SCREEN_NAME
            ],
            $accessorBuilder::MEMBER_SCREEN_NAME,
        )->build();

        return $accessor;
    }

    /**
     * @return MemberRepository
     * @throws InvalidMemberIdentifier
     */
    private function prophesizeMemberRepository(): MemberRepository
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
     * @throws InvalidSerializedTokenException
     */
    private function prophesizeTokenChange(): TokenChangeInterface
    {
        $tokenChangeBuilder = new TokenChangeBuilder();
        $tokenChangeBuilder = $tokenChangeBuilder->willReplaceAccessToken(
            Token::fromArray(
                [
                    'token'           => self::USER_TOKEN_SECONDARY,
                    'secret'          => self::USER_SECRET_SECONDARY,
                    'consumer_key'    => self::CONSUMER_KEY,
                    'consumer_secret' => self::CONSUMER_SECRET
                ]
            )
        );

        return $tokenChangeBuilder->build();
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
