<?php
declare (strict_types=1);

namespace App\NewsReview\Infrastructure\Routing\Entity;

use App\NewsReview\Domain\Routing\Model\PublishersListInterface;
use Ramsey\Uuid\UuidInterface;

class PublishersList implements PublishersListInterface
{
    private string $name;
    private UuidInterface $publicId;

    public function __construct(string $name, UuidInterface $publicId)
    {
        $this->name = $name;
        $this->publicId = $publicId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function publicId(): UuidInterface
    {
        return $this->publicId;
    }
}