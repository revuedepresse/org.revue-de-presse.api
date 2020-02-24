<?php
declare(strict_types=1);

namespace App\Tests\Builder;

use App\Domain\Resource\MemberIdentity;
use App\Infrastructure\Repository\Membership\MemberRepository;
use App\Infrastructure\Repository\Membership\MemberRepositoryInterface;
use App\Membership\Entity\Member;
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
        $this->prophecy = $prophet->prophesize(MemberRepository::class);
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

    public function build(): MemberRepositoryInterface
    {
        return $this->prophecy->reveal();
    }
}