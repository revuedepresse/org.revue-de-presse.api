<?php
declare (strict_types=1);

namespace App\Tests\Membership\Builder\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Membership\Domain\Repository\NetworkRepositoryInterface;
use App\Membership\Infrastructure\Repository\NetworkRepository;
use App\Tests\Membership\Builder\Entity\Legacy\MemberBuilder;
use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\ApiAccessorBuilder;
use PDOException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Throwable;

class NetworkRepositoryBuilder extends TestCase
{
    public static function build(\App\Membership\Domain\Repository\MemberRepositoryInterface $repository, LoggerInterface $logger)
    {
        $testCase = new class() extends TestCase {
            use ProphecyTrait;

            public function __construct()
            {
                $this->prophet = $this->getProphet();
            }

            public function prophesize(?string $classOrInterface = null): ObjectProphecy {
                return $this->prophet->prophesize($classOrInterface);
            }
        };

        /** @var NetworkRepositoryInterface|ObjectProphecy $prophecy */
        $prophecy = $testCase->prophesize(NetworkRepository::class);

        try {
            $member = self::ensureMembersExistsInDatabase($repository);
        } catch (Throwable $e) {
            if ($e instanceof PDOException) {
                $logger->error($e->getMessage(), ['exception' => $e]);
            }

            return $prophecy->reveal();
        }

        $prophecy->ensureMemberExists(Argument::type('string'))
            ->willReturn($member);

        return $prophecy->reveal();
    }

    /**
     * @param \App\Membership\Domain\Repository\MemberRepositoryInterface $repository
     * @return MemberInterface
     */
    private static function ensureMembersExistsInDatabase(\App\Membership\Domain\Repository\MemberRepositoryInterface $repository): MemberInterface
    {
        $twitterId = ApiAccessorBuilder::PUBLISHERS_LIST_MEMBER_TWITTER_ID;

        $member = MemberBuilder::build(
            ApiAccessorBuilder::PUBLISHERS_LIST_MEMBER_SCREEN_NAME,
            $twitterId
        );
        $existingMember = $repository->findOneBy(['twitterID' => $twitterId]);

        if ($existingMember instanceof MemberInterface) {
            return $existingMember;
        }

        return $repository->saveMember($member);
    }

}
