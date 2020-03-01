<?php
declare(strict_types=1);

namespace App\Infrastructure\Identification;

use App\Api\Entity\Whisperer;
use App\Domain\Collection\CollectionStrategyInterface;
use App\Infrastructure\Amqp\Message\FetchPublicationInterface;
use App\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\DependencyInjection\Membership\WhispererRepositoryTrait;
use App\Infrastructure\DependencyInjection\Status\LikedStatusRepositoryTrait;
use App\Infrastructure\DependencyInjection\Status\StatusLoggerTrait;
use App\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Status\Repository\ExtremumAwareInterface;
use function array_key_exists;

class WhispererIdentification implements WhispererIdentificationInterface
{
    use ApiAccessorTrait;
    use StatusLoggerTrait;
    use LikedStatusRepositoryTrait;
    use LoggerTrait;
    use StatusRepositoryTrait;
    use TranslatorTrait;
    use WhispererRepositoryTrait;

    public function identifyWhisperer(
        CollectionStrategyInterface $collectionStrategy,
        array $options,
        string $screenName,
        ?int $lastCollectionBatchSize
    ): bool {
        $flaggedWhisperer = false;

        if (!$this->justCollectedSomeStatuses($lastCollectionBatchSize)) {
            $member = $this->apiAccessor->showUser($screenName);

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

            $flaggedWhisperer = true;
        }

        return $flaggedWhisperer;
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