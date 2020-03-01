<?php
declare(strict_types=1);

namespace App\Domain\Publication;

use Symfony\Component\HttpFoundation\Request;

final class PublicationListIdentity
{
    private int $id;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function __toString()
    {
        return (string) $this->id;
    }

    public static function fromRequest(Request $request): ?self
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
