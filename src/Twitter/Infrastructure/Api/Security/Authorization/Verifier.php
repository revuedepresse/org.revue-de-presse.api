<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Security\Authorization;

use App\Twitter\Domain\Api\Security\Authorization\VerifierInterface;

class Verifier implements VerifierInterface
{
    private int $verifier;

    public function __construct(int $verifier)
    {
        $this->verifier = $verifier;
    }

    public function verifier(): int
    {
        return $this->verifier;
    }
}