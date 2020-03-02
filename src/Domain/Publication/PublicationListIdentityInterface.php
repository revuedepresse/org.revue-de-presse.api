<?php
declare(strict_types=1);

namespace App\Domain\Publication;

use Symfony\Component\HttpFoundation\Request;

interface PublicationListIdentityInterface
{
    public function __construct(int $id);

    public function __toString(): string;

    public static function fromRequest(Request $request): ?PublicationListIdentityInterface;
}
