<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Repository;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * @package App\Twitter\Entity
 */
interface PublicationInterface
{
    public function getId(): UuidInterface;

    public function getLegacyId(): int;

    public function getHash(): string;

    public function getAvatarUrl(): string;

    public function getScreenName(): string;

    public function getText(): string;

    public function getDocumentId(): string;

    public function getDocument(): string;

    public function getPublishedAt(): DateTimeInterface;
}