<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Resource;

use App\Twitter\Infrastructure\Api\Entity\TokenInterface;

class MemberOwnerships
{
    /**
     * @var TokenInterface
     */
    private TokenInterface $token;

    /**
     * @var OwnershipCollection
     */
    private OwnershipCollection $ownershipCollection;

    private function __construct(
        TokenInterface $token,
        OwnershipCollection $ownershipCollection
    )
    {
        $this->token = $token;
        $this->ownershipCollection = $ownershipCollection;
    }

    public static function from(
        TokenInterface $token,
        OwnershipCollection $ownershipCollection
    ): MemberOwnerships {
        return new self($token, $ownershipCollection);
    }

    public function token(): TokenInterface
    {
        return $this->token;
    }

    public function ownershipCollection(): OwnershipCollection
    {
        return $this->ownershipCollection;
    }
}