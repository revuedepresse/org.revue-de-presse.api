<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Resource;

use App\Twitter\Domain\Http\Model\TokenInterface;

class MemberOwnerships
{
    /**
     * @var TokenInterface
     */
    private TokenInterface $token;

    private OwnershipCollectionInterface $ownershipCollection;

    private function __construct(
        TokenInterface $token,
        OwnershipCollectionInterface $ownershipCollection
    )
    {
        $this->token = $token;
        $this->ownershipCollection = $ownershipCollection;
    }

    public static function from(
        TokenInterface $token,
        OwnershipCollectionInterface $ownershipCollection
    ): MemberOwnerships {
        return new self($token, $ownershipCollection);
    }

    public function token(): TokenInterface
    {
        return $this->token;
    }

    public function ownershipCollection(): OwnershipCollectionInterface
    {
        return $this->ownershipCollection;
    }
}