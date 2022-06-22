<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Persistence;

use App\Twitter\Infrastructure\Api\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use Doctrine\ORM\EntityManagerInterface;

interface StatusPersistenceInterface
{
    public function persistAllStatuses(
        array $statuses,
        AccessToken $accessToken,
        PublishersList $aggregate = null
    ): array;

    public function unarchiveStatus(
        StatusInterface $status,
        EntityManagerInterface $entityManager
    ): StatusInterface;

    public function savePublicationsForScreenName(
        array $statuses,
        string $screenName,
        CurationSelectorsInterface $selectors
    );
}