<?php
declare (strict_types=1);

namespace App\NewsReview\Infrastructure\Routing\Entity;

use App\NewsReview\Domain\Routing\Model\RouteInterface;
use Ramsey\Uuid\UuidInterface;

class PublishersListRoute implements RouteInterface
{
    private UuidInterface $id;
    private UuidInterface $publicId;
    private string $hostname;

    public function __construct(
        UuidInterface $id,
        UuidInterface $publicId,
        string $hostname
    ) {
        $this->id = $id;
        $this->publicId = $publicId;
        $this->hostname = $hostname;
    }

    public function hostname(): string
    {
        return $this->hostname;
    }

    public function toArray(): array
    {
        return ['hostname' => $this->hostname];
    }
}