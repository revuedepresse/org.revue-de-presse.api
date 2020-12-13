<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Membership;

use App\Twitter\Infrastructure\Identification\WhispererIdentificationInterface;

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