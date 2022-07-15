<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Identification;

use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchAuthoredTweetInterface;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\MemberProfileCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Http\HttpClientTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\WhispererRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\TweetCurationLoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\TweetRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Infrastructure\Http\Entity\Whisperer;

class WhispererIdentification implements WhispererIdentificationInterface
{
    use HttpClientTrait;
    use LoggerTrait;
    use MemberProfileCollectedEventRepositoryTrait;
    use TweetCurationLoggerTrait;
    use TweetRepositoryTrait;
    use TranslatorTrait;
    use WhispererRepositoryTrait;

    public function identifyWhisperer(
        CurationSelectorsInterface $selectors,
        array                      $options,
        string                     $screenName,
        ?int                       $lastCollectionBatchSize
    ): bool {
        if ($this->justCollectedSomeStatuses($lastCollectionBatchSize)) {
            return false;
        }

        $eventRepository = $this->memberProfileCollectedEventRepository;
        $member = $eventRepository->collectedMemberProfile(
            $this->httpClient,
            [$eventRepository::OPTION_SCREEN_NAME => $screenName]
        );

        $totalCollectedStatuses = 0;
        try {
            $totalCollectedStatuses = $this->logHowManyItemsHaveBeenCollected(
                $selectors,
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
     * @param CurationSelectorsInterface $selectors
     *
     * @param array                      $options
     * @param int|null                   $lastCollectionBatchSize
     *
     * @return mixed
     */
    private function logHowManyItemsHaveBeenCollected(
        CurationSelectorsInterface $selectors,
        array                      $options,
        ?int                       $lastCollectionBatchSize
    ) {
        $selectors->selectTweetsByMemberScreenName($options[FetchAuthoredTweetInterface::SCREEN_NAME]);

        $subjectInSingularForm = 'status';
        $subjectInPluralForm   = 'statuses';
        $countCollectedItems   = function (
            string $memberName
        ) {
            return $this->tweetRepository->countCollectedStatuses(
                $memberName,
                $maxId = PHP_INT_MAX
            );
        };

        $totalStatuses = $countCollectedItems(
            $selectors->screenName(),
        );

        $this->collectStatusLogger->logHowManyItemsHaveBeenCollected(
            $selectors,
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
