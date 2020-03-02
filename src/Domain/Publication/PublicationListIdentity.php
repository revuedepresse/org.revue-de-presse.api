<?php
declare(strict_types=1);

namespace App\Domain\Publication;

use Symfony\Component\HttpFoundation\Request;

final class PublicationListIdentity implements PublicationListIdentityInterface
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

    public static function fromRequest(Request $request): ?PublicationListIdentityInterface
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
