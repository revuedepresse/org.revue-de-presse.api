<?php
declare(strict_types=1);

namespace App\Infrastructure\Identification;

use App\Api\Entity\Whisperer;
use App\Infrastructure\DependencyInjection\ApiAccessorTrait;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\DependencyInjection\Membership\WhispererRepositoryTrait;
use App\Infrastructure\DependencyInjection\TranslatorTrait;

class WhispererIdentification implements WhispererIdentificationInterface
{
    use ApiAccessorTrait;
    use LoggerTrait;
    use TranslatorTrait;
    use WhispererRepositoryTrait;

    public function identifyWhisperer(
        string $screenName,
        int $totalCollectedStatuses,
        ?int $lastCollectionBatchSize
    ): bool {
        $flaggedWhisperer = false;

        if (!$this->justCollectedSomeStatuses($lastCollectionBatchSize)) {
            $member = $this->apiAccessor->showUser($screenName);

            $whisperer = new Whisperer($screenName, $totalCollectedStatuses);
            $whisperer->setExpectedWhispers($member->statuses_count);

            $this->whispererRepository->declareWhisperer($whisperer);

            $whispererDeclarationMessage = $this->translator->trans(
                'logs.info.whisperer_declared',
                ['screen name' => $screenName],
                'logs'
            );
            $this->logger->info($whispererDeclarationMessage);

            $flaggedWhisperer = true;
        }

        return $flaggedWhisperer;
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