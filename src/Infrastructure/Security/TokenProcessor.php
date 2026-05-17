<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProcessorInterface<null, AccessTokenDto>
 */
final class TokenProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AccessTokenMinter $minter,
        private readonly BasicClientCredentialsExtractor $extractor,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AccessTokenDto
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new \LogicException('No current request');
        }

        $member = $this->extractor->extract($request);
        $token = $this->minter->mint((string) $member->getId());

        return new AccessTokenDto(
            access_token: $token,
            token_type: 'Bearer',
            expires_in: $this->minter->ttlSeconds(),
        );
    }
}
