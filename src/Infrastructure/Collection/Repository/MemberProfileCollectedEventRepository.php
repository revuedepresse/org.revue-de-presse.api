<?php
declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Domain\Collection\Entity\MemberProfileCollectedEvent;
use App\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Api\ApiAccessorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use stdClass;
use Throwable;
use const JSON_THROW_ON_ERROR;

class MemberProfileCollectedEventRepository extends ServiceEntityRepository implements MemberProfileCollectedEventRepositoryInterface
{
    use LoggerTrait;
    use ApiAccessorTrait;

    public function collectedMemberProfile(
        ApiAccessorInterface $accessor,
        array $options
    ): stdClass {
        $screenName = $options[self::OPTION_SCREEN_NAME];

        $event = $this->startCollectOfOwnershipBatch($screenName);

        $twitterMember = $accessor->getMemberProfile($screenName);

        $this->finishCollectOfOwnershipBatch(
            $event,
            json_encode(
                [
                    'method'   => 'getMemberProfile',
                    'options'  => $options,
                    'response' => (array) $twitterMember,
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $twitterMember;
    }

    private function finishCollectOfOwnershipBatch(
        MemberProfileCollectedEvent $event,
        string $payload
    ): MemberProfileCollectedEvent {
        $event->finishCollect($payload);

        return $this->save($event);
    }

    private function save(MemberProfileCollectedEvent $event): MemberProfileCollectedEvent
    {
        $entityManager = $this->getEntityManager();

        try {
            $entityManager->persist($event);
            $entityManager->flush();
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $event;
    }

    private function startCollectOfOwnershipBatch(
        string $screenName
    ): MemberProfileCollectedEvent {
        $now = new \DateTimeImmutable();

        $event = new MemberProfileCollectedEvent(
            $screenName,
            $now,
            $now
        );

        return $this->save($event);
    }
}