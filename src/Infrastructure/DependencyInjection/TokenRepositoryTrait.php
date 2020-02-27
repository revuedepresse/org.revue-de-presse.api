<?php

namespace App\Infrastructure\DependencyInjection;

use App\Api\AccessToken\Repository\TokenRepositoryInterface;

trait TokenRepositoryTrait
{
    /**
     * @var TokenRepositoryInterface $tokenRepository
     */
    protected TokenRepositoryInterface $tokenRepository;

    /**
     * @param TokenRepositoryInterface $tokenRepository
     * @return $this
     */
    public function setTokenRepository(TokenRepositoryInterface $tokenRepository): self
    {
        $this->tokenRepository = $tokenRepository;

        return $this;
    }

}