<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Selector;

use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Api\Selector\AuthenticatedSelectorInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdAwareInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdInterface;

class AuthenticatedSelector implements AuthenticatedSelectorInterface, CorrelationIdAwareInterface
{
    private CorrelationIdInterface $correlationId;

    private TokenInterface $authenticationToken;

    private string $screenName;

    public function __construct(
        TokenInterface $authenticationToken,
        string $screenName,
        CorrelationIdInterface $correlationId = null
    ) {
        $this->authenticationToken = $authenticationToken;
        $this->screenName = $screenName;

        if ($correlationId === null) {
            $correlationId = CorrelationId::generate();
        }

        $this->correlationId = $correlationId;
    }

    public function correlationId(): CorrelationIdInterface
    {
        return $this->correlationId;
    }

    public function authenticationToken(): TokenInterface
    {
        return $this->authenticationToken;
    }

    public function screenName(): string
    {
        return $this->screenName;
    }
}