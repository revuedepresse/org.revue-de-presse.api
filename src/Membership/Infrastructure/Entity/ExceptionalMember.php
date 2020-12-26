<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\Entity;

use App\Membership\Domain\Entity\TwitterMemberInterface;

class ExceptionalMember implements TwitterMemberInterface
{
    use MemberTrait;
    use ExceptionalUserInterfaceTrait;
}
