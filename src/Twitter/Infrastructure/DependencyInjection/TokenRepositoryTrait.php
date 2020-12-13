<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use App\Twitter\Infrastructure\Api\AccessToken\Repository\TokenRepositoryInterface;

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