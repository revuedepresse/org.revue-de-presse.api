<?php

namespace App\Recommendation\Command;

use App\Console\CommandReturnCodeAwareInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RecommendSubscriptionsCommand extends Command implements CommandReturnCodeAwareInterface
{
    const OPTION_REFERENCE_MEMBER = 'reference-member';

    /**
     * @var array
     */
    private $allSubscriptions;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var EntityManager
     */
    public $entityManager;

    public function configure()
    {
        $this->setName('recommend:subscriptions')
            ->addOption(
                self::OPTION_REFERENCE_MEMBER,
                'r',
                InputOption::VALUE_REQUIRED,
                'The screen name of a member to recommend subscriptions from her / his history'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        $allSubscriptions = $this->entityManager->getConnection()->executeQuery('
            SELECT GROUP_CONCAT(distinct subscription_id) all_subscription_ids FROM member_subscription
        ')->fetchAll()[0]['all_subscription_ids'];

        $allSubscriptions = explode(',', $allSubscriptions);
        $allSubscriptions = array_map(
            function ($subscription) {
                return intval($subscription);
            },
            $allSubscriptions
        );
        array_unshift($allSubscriptions, null);

        $this->allSubscriptions = $allSubscriptions;

        $query = <<<QUERY
            SELECT member_id,
            GROUP_CONCAT(
                FIND_IN_SET(
                    subscription_id,
                    (
                        SELECT group_concat(DISTINCT subscription_id) FROM member_subscription
                    )
                )
            ) subscription_ids 
            FROM member_subscription
            WHERE member_id IN (
              SELECT usr_id 
              FROM weaving_user 
              WHERE usr_twitter_username = ":member_name"
            )
            GROUP BY member_id
QUERY;

        $statement = $this->entityManager->getConnection()->executeQuery(
            str_replace(':member_name', $this->input->getOption(self::OPTION_REFERENCE_MEMBER), $query)
        );
        $results = $statement->fetchAll();

        if (!array_key_exists(0, $results)) {
            throw new \LogicException('There should be subscriptions for the reference member');
        }

        $initialVector = $results[0]['subscription_ids'];
        $totalSignificantReferenceSubscriptions = count(explode(',', $initialVector));
        $initialVector = $this->reduceMemberVector($initialVector);

        $query = <<<QUERY
            SELECT 
            u.usr_twitter_username identifier, 
            GROUP_CONCAT(
                FIND_IN_SET(
                  subscription_id,
                  (
                    SELECT group_concat(DISTINCT subscription_id) FROM member_subscription
                  )
                )
            ) subscription_ids,
            COUNT(DISTINCT subscription_id) total_subscriptions
            FROM member_subscription s, weaving_user u 
            WHERE u.usr_id = s.member_id
            GROUP BY member_id
            HAVING total_subscriptions BETWEEN :min AND :max
            LIMIT 100
QUERY;
        $statement = $this->entityManager->getConnection()
            ->executeQuery(
                strtr(
                    $query,
                    [
                        ':min' => $totalSignificantReferenceSubscriptions * 0.75,
                        ':max' => $totalSignificantReferenceSubscriptions * 1.25
                    ]
                )
            );
        $results = $statement->fetchAll();

        $memberVectors = array_map(function ($record) {
            return $this->reduceMemberVector($record['subscription_ids']);
        }, $results);

        array_unshift($memberVectors, $initialVector);

        $distances = array_map(
            function ($memberVector, $index) use ($initialVector) {
                $powers = array_map(
                    function ($initialVectorCoordinate, $memberVectorCoordinate) {
                        if (is_null($initialVectorCoordinate)) {
                            $initialVectorCoordinate = 0;
                        }

                        if (is_null($memberVectorCoordinate)) {
                            $memberVectorCoordinate = 0;
                        }

                        return pow($initialVectorCoordinate - $memberVectorCoordinate, 2);
                    },
                    $memberVector,
                    $initialVector
                );

                return [
                    'distance' => sqrt(array_sum($powers)),
                    'vector' => $memberVector,
                    'vector_index' => $index,
                ];
            },
            $memberVectors,
            array_keys($memberVectors)
        );

        usort($distances, function ($vectorA, $vectorB) {
            if ($vectorA['distance'] === $vectorB['distance']) {
                return 0;
            }

            if ($vectorA['distance'] > $vectorB['distance']) {
                return -1;
            }

            return 1;
        });

        array_unshift($results, ['identifier' => 'ref']);

        $sortedDistances = array_map(
            function ($distance) use ($results) {
                $distance['member'] = $results[$distance['vector_index']]['identifier'];

                return $distance;
            },
            $distances
        );

        return $sortedDistances;
    }

    /**
     * @param $initialVector
     * @return int|mixed
     */
    private function reduceMemberVector($initialVector)
    {
        $positions = array_values(explode(',', $initialVector));
        $positions = array_map('intval', $positions);
        $flippedPositions = array_flip($positions);

        return array_map(function ($subscription, $index) use ($flippedPositions) {
            if (array_key_exists($index, $flippedPositions)) {
                // Normalization
                return $subscription / $subscription;
            }

            return null;
        }, $this->allSubscriptions, array_keys($this->allSubscriptions));
    }
}
