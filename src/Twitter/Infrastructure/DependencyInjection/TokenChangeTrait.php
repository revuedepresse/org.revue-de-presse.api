<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use App\Twitter\Infrastructure\Api\AccessToken\TokenChangeInterface;

trait TokenChangeTrait
{
    /**
     * @var TokenChangeInterface
     */
    private TokenChangeInterface $tokenChange;

    public function setTokenChange(TokenChangeInterface $tokenChange): self
    {
        $this->tokenChange = $tokenChange;

        return $this;
    }

}