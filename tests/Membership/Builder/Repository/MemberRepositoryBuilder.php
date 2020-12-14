<?php
declare(strict_types=1);

namespace App\Tests\Membership\Builder\Repository;

use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Membership\Domain\Entity\Legacy\Member;
use App\Membership\Domain\Entity\MemberInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

class MemberRepositoryBuilder
{
    /**
     * @var ObjectProphecy
     */
    private ObjectProphecy $prophecy;

    private function __construct()
    {
        $prophet = new Prophet();
        $this->prophecy = $prophet->prophesize(MemberRepositoryInterface::class);
    }

    public static function newMemberRepositoryBuilder()
    {
        return new self();
    }

    public function willSaveAProtectedMember(): self
    {
        $this->prophecy->saveProtectedMember(
            Argument::type(MemberIdentity::class)
        )
        ->willReturn(new Member());

        return $this;
    }

    public function willSaveASuspendedMember(): self
    {
        $this->prophecy->saveSuspendedMember(
            Argument::type(MemberIdentity::class)
        )
        ->willReturn(new Member());

        return $this;
    }

    public function willSaveMemberFromIdentity(): self
    {
        $this->prophecy->saveMemberFromIdentity(
            Argument::type(MemberIdentity::class)
        )
        ->willReturn(new Member());

        return $this;
    }

    public function willFindAMemberByTwitterId(string $twitterId, $member): self
    {
        $this->prophecy->findOneBy(['twitterID' => $twitterId])
            ->willReturn($member);

        return $this;
    }

    public function willFindAMemberByTwitterScreenName(string $screenName, MemberInterface $member): self
    {
        $this->prophecy->findOneBy(['twitter_username' => $screenName])
            ->willReturn($member);

        return $this;
    }

    public function willDeclareAMemberAsFound(MemberInterface $member): self
    {
        $this->prophecy->declareMemberAsFound($member)
            ->will(function ($args) {
                $args[0]->setNotFound(false);

                return $args[0];
            });

        return $this;
    }

    public function build(): MemberRepositoryInterface
    {
        return $this->prophecy->reveal();
    }
}