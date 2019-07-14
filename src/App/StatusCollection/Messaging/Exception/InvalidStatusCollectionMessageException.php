<?php

namespace App\StatusCollection\Messaging\Exception;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository;

class InvalidStatusCollectionMessageException extends \Exception
{
    /**
     * @param AggregateRepository $repository
     * @param array               $options
     * @return mixed|string
     * @throws InvalidStatusCollectionMessageException
     */
    public static function ensureMessageContainsValidScreenName(
        AggregateRepository $repository,
        array $options
    ): string {

        $aggregateId = self::extractAggregateId($options);
        $availableScreenName = array_key_exists('screen_name', $options);

        if ($availableScreenName) {
            return $options['screen_name'];
        }

        if (is_null($aggregateId)) {
            throw new self('Invalid screen name');
        }

        $aggregate = $repository->findOneBy(['id' => $aggregateId]);
        if ($aggregate instanceof Aggregate && $aggregate->isMemberAggregate()) {
            return $aggregate->screenName;
        }

        throw new self('Invalid aggregate id');
    }

    /**
     * @param $options
     * @return null
     */
    private static function extractAggregateId($options)
    {
        if (array_key_exists('aggregate_id', $options)) {
            $aggregateId = $options['aggregate_id'];
        } else {
            $aggregateId = null;
        }

        return $aggregateId;
    }
}
