<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Repository;

use App\Twitter\Domain\Curation\Entity\MemberFriendsCollectedEvent;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Domain\Api\ApiAccessorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use stdClass;
use Throwable;
use const JSON_THROW_ON_ERROR;

class MemberFriendsCollectedEventRepository extends ServiceEntityRepository implements MemberFriendsCollectedEventRepositoryInterface
{
    use LoggerTrait;
    use ApiAccessorTrait;

    public function collectedMemberFriends(
        ApiAccessorInterface $accessor,
        array $options
    ): stdClass {
        $screenName = $options[self::OPTION_SCREEN_NAME];

        $event = $this->startCollectOfMemberFriends($screenName);

        $friends = $accessor->getFriendsOfMemberHavingScreenName($screenName);

        $this->finishCollectOfMemberFriends(
            $event,
            json_encode(
                [
                    'method'   => 'getFriendsOfMemberHavingScreenName',
                    'options'  => $options,
                    'response' => (array) $friends,
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $friends;
    }

    private function finishCollectOfMemberFriends(
        MemberFriendsCollectedEvent $event,
        string $payload
    ): MemberFriendsCollectedEvent {
        $event->finishCollect($payload);

        return $this->save($event);
    }

    private function save(MemberFriendsCollectedEvent $event): MemberFriendsCollectedEvent
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

    private function startCollectOfMemberFriends(
        string $screenName
    ): MemberFriendsCollectedEvent {
        $now = new \DateTimeImmutable();

        $event = new MemberFriendsCollectedEvent(
            $screenName,
            $now,
            $now
        );

        return $this->save($event);
    }
}