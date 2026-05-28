<?php
declare(strict_types=1);

namespace App\Security\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Security\Domain\DeviceTokenDto;
use App\Security\Domain\DeviceTokenMinter;
use App\Security\Infrastructure\ApiPlatform\Resource\DeviceTokenMintRequest;

/**
 * @implements ProcessorInterface<DeviceTokenMintRequest, DeviceTokenDto>
 */
final readonly class DeviceTokenProcessor implements ProcessorInterface
{
    public function __construct(private DeviceTokenMinter $minter)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DeviceTokenDto
    {
        // The {platform, appVersion, installId} payload is currently consumed
        // for shape validation only — every device token resolves to the same
        // configured Member ID. Per-installId provenance would land here once
        // an InstallIdRegistry exists (separate change).
        return $this->minter->mint();
    }
}
