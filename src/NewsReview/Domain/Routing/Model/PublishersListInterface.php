<?php

namespace App\NewsReview\Domain\Routing\Model;

use Ramsey\Uuid\UuidInterface;

interface PublishersListInterface
{
    public function name(): string;

    public function publicId(): UuidInterface;
}