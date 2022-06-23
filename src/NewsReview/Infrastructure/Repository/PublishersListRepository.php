<?php

declare (strict_types=1);

namespace App\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Repository\PublishersListRepositoryInterface;
use App\NewsReview\Domain\Routing\Model\PublishersListInterface;
use App\NewsReview\Domain\Exception\UnknownPublishersListException;
use App\NewsReview\Infrastructure\Routing\Entity\PublishersList;
use App\Twitter\Domain\Publication\PublishersListInterface as TwitterPublishersListInterface;
use App\Twitter\Domain\Publication\Repository\PublishersListRepositoryInterface as TwitterPublishersListRepositoryInterface;

class PublishersListRepository implements PublishersListRepositoryInterface
{
    private TwitterPublishersListRepositoryInterface $twitterRepository;

    public function __construct(TwitterPublishersListRepositoryInterface $repository)
    {
        $this->twitterRepository = $repository;
    }

    public function findByName(string $name): PublishersListInterface
    {
        $publishersList = $this->twitterRepository->findOneBy(['name' => $name]);

        if ($publishersList instanceof TwitterPublishersListInterface) {
            return new PublishersList($publishersList->name(), $publishersList->publicId());
        }

        UnknownPublishersListException::throws();
    }
}