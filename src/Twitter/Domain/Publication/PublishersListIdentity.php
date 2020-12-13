<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication;

use Symfony\Component\HttpFoundation\Request;

final class PublishersListIdentity implements PublishersListIdentityInterface
{
    private int $id;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public static function fromRequest(Request $request): ?PublishersListIdentityInterface
    {
        $aggregateIdentity = null;
        if ($request->get('aggregateId')) {
            $aggregateIdentity = new self(
                (int) ($request->get('aggregateId'))
            );
        }

        return $aggregateIdentity;
    }
}
