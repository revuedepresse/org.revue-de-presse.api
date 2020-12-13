<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\Api\AccessToken\Repository\TokenRepositoryInterface;

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