<?php
declare(strict_types=1);

namespace App\Membership\Domain\Entity;

class ExceptionalMember implements TwitterMemberInterface
{
    use MemberTrait;
    use ExceptionalUserInterfaceTrait;
}
