<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Membership;

use App\Infrastructure\Identification\WhispererIdentificationInterface;

trait WhispererIdentificationTrait
{
    private WhispererIdentificationInterface $whispererIdentification;

    public function setWhispererIdentification(
        WhispererIdentificationInterface $whispererIdentification
    ): self {
        $this->whispererIdentification = $whispererIdentification;

        return $this;
    }
}