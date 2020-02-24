<?php
declare(strict_types=1);

namespace App\Tests\Security\Core\Member;

use Doctrine\ORM\Mapping as ORM;
use App\Membership\Entity\Member as BaseMember;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @ORM\Entity
 * @ORM\MappedSuperclass
 */
class Member extends BaseMember
{
    public function __construct(
        $username,
        $password,
        array $roles = array(),
        $enabled = true,
        $userNonExpired = true,
        $credentialsNonExpired = true,
        $userNonLocked = true
    ) {
        if (empty($username)) {
            throw new \InvalidArgumentException('The username cannot be empty.');
        }

        $this->username = $username;
        $this->password = $password;
        $this->enabled = $enabled;
        $this->positionInHierarchy = 0;
    }
}
