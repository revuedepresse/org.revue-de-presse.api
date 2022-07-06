<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Http\Security\Authorization;

interface VerifierInterface
{
    public function verifier(): int;
}