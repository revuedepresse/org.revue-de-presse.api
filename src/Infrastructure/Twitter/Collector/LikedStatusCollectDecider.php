<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Collector;

use App\Domain\Curation\CollectionStrategyInterface;
use App\Domain\Publication\PublishersListInterface;
use App\Infrastructure\Amqp\Message\FetchPublicationInterface;
use App\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Infrastructure\DependencyInjection\Api\StatusAccessorTrait;
use App\Infrastructure\DependencyInjection\Status\LikedStatusRepositoryTrait;
use App\Infrastructure\DependencyInjection\Status\StatusPersistenceTrait;
use App\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use stdClass;
use function count;

class LikedStatusCollectDecider implements LikedStatusCollectDeciderInterface
{
    use ApiAccessorTrait;
    use LikedStatusRepositoryTrait;
    use StatusPersistenceTrait;
    use StatusAccessorTrait;
    use StatusRepositoryTrait;

    public function shouldSkipLikedStatusCollect(
        array $options,
        array $statuses,
        CollectionStrategyInterface $collectionStrategy,
        ?PublishersListInterface $publishersList
    ): bool {
        $atLeastOneStatusFetched = count($statuses) > 0;

        $hasLikedStatusBeenSavedBefore = $this->hasOneLikedStatusAtLeastBeenSavedBefore(
            $options[FetchPublicationInterface::SCREEN_NAME],
            $atLeastOneStatusFetched,
            $publishersList,
            $statuses[0]
        );

        if ($atLeastOneStatusFetched && !$hasLikedStatusBeenSavedBefore) {
            // At this point, it should not skip further consumption
            // for matching liked statuses
            $this->statusPersistence->savePublicationsForScreenName(
                $statuses,
                $options[FetchPublicationInterface::SCREEN_NAME],
                $collectionStrategy
            );

            $this->statusRepository->declareMinimumLikedStatusId(
                $statuses[count($statuses) - 1],
                $options[FetchPublicationInterface::SCREEN_NAME]
            );
        }

        if (!$atLeastOneStatusFetched || $hasLikedStatusBeenSavedBefore) {
            $statuses = $this->statusAccessor->fetchPublications(
                $collectionStrategy,
                $options,
                $discoverPastTweets = false
            );
            if (count($statuses) > 0) {
                if (
                $this->statusRepository->hasBeenSavedBefore(
                    [$statuses[0]]
                )
                ) {
                    return true;
                }

                $collectionStrategy->optInToCollectStatusForPublishersListOfId(
                    $options[FetchPublicationInterface::publishers_list_ID]
                );

                // At this point, it should not skip further consumption
                // for matching liked statuses
                $this->statusPersistence->savePublicationsForScreenName(
                    $statuses,
                    $options[FetchPublicationInterface::SCREEN_NAME],
                    $collectionStrategy
                );

                $this->statusRepository->declareMaximumLikedStatusId(
                    $statuses[0],
                    $options[FetchPublicationInterface::SCREEN_NAME]
                );
            }

            return true;
        }

        return false;
    }

    /**
     * @param string                        $screenNameOfMemberWhoLikedStatus
     * @param bool                          $atLeastOneStatusFetched
     * @param PublishersListInterface|null $publishersList
     * @param stdClass                     $firstStatus
     *
     * @return bool
     */
    private function hasOneLikedStatusAtLeastBeenSavedBefore(
        string $screenNameOfMemberWhoLikedStatus,
        bool $atLeastOneStatusFetched,
        ?PublishersListInterface $publishersList,
        stdClass $firstStatus
    ): bool {
        if (!$atLeastOneStatusFetched) {
            return false;
        }

        if (!($publishersList instanceof PublishersListInterface)) {
            return false;
        }

        return $this->likedStatusRepository->hasBeenSavedBefore(
            $firstStatus,
            $publishersList->getName(),
            $screenNameOfMemberWhoLikedStatus,
            $firstStatus->user->screen_name
        );
    }
}