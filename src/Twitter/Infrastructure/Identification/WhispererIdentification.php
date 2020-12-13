<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Identification;

use App\Twitter\Infrastructure\Api\Entity\Whisperer;
use App\Twitter\Domain\Curation\CollectionStrategyInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchPublicationInterface;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Collection\MemberProfileCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\WhispererRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\LikedStatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusLoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;

class WhispererIdentification implements WhispererIdentificationInterface
{
    use ApiAccessorTrait;
    use LikedStatusRepositoryTrait;
    use LoggerTrait;
    use MemberProfileCollectedEventRepositoryTrait;
    use StatusLoggerTrait;
    use StatusRepositoryTrait;
    use TranslatorTrait;
    use WhispererRepositoryTrait;

    public function identifyWhisperer(
        CollectionStrategyInterface $collectionStrategy,
        array $options,
        string $screenName,
        ?int $lastCollectionBatchSize
    ): bool {
        if ($this->justCollectedSomeStatuses($lastCollectionBatchSize)) {
            return false;
        }

        $eventRepository = $this->memberProfileCollectedEventRepository;
        $member = $eventRepository->collectedMemberProfile(
            $this->apiAccessor,
            [$eventRepository::OPTION_SCREEN_NAME => $screenName]
        );

        $totalCollectedStatuses = 0;
        try {
            $totalCollectedStatuses = $this->logHowManyItemsHaveBeenCollected(
                $collectionStrategy,
                $options,
                $lastCollectionBatchSize
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                ['stacktrack' => $exception->getTrace()]
            );
        }

        $whisperer = new Whisperer($screenName, $totalCollectedStatuses);
        $whisperer->setExpectedWhispers($member->statuses_count);

        $this->whispererRepository->declareWhisperer($whisperer);

        $whispererDeclarationMessage = $this->translator->trans(
            'logs.info.whisperer_declared',
            ['screen_name' => $screenName],
            'logs'
        );
        $this->logger->info($whispererDeclarationMessage);

        return true;
    }

    /**
     * @param CollectionStrategyInterface $collectionStrategy
     *
     * @param array                       $options
     * @param int|null                    $lastCollectionBatchSize
     *
     * @return mixed
     */
    private function logHowManyItemsHaveBeenCollected(
        CollectionStrategyInterface $collectionStrategy,
        array $options,
        ?int $lastCollectionBatchSize
    ) {
        $collectionStrategy->optInToCollectStatusFor($options[FetchPublicationInterface::SCREEN_NAME]);

        $subjectInSingularForm = 'status';
        $subjectInPluralForm   = 'statuses';
        $countCollectedItems   = function (
            string $memberName
        ) {
            return $this->statusRepository->countCollectedStatuses(
                $memberName,
                $maxId = INF
            );
        };
        if ($collectionStrategy->fetchLikes()) {
            $subjectInSingularForm = 'like';
            $subjectInPluralForm   = 'likes';
            $countCollectedItems   = function (
                string $memberName
            ) {
                return $this->likedStatusRepository->countCollectedLikes(
                    $memberName,
                    $maxId = INF
                );
            };
        }

        $totalStatuses = $countCollectedItems(
            $collectionStrategy->screenName(),
        );

        $this->collectStatusLogger->logHowManyItemsHaveBeenCollected(
            $collectionStrategy,
            (int) $totalStatuses,
            [
                'plural'   => $subjectInPluralForm,
                'singular' => $subjectInSingularForm
            ],
            (int) $lastCollectionBatchSize
        );

        return $totalStatuses;
    }

    /**
     * @param $statuses
     *
     * @return bool
     */
    public function justCollectedSomeStatuses($statuses): bool
    {
        return $statuses !== null && $statuses > 0;
    }
}