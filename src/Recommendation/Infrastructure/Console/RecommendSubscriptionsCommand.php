<?php
declare(strict_types=1);

namespace App\Recommendation\Infrastructure\Console;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RecommendSubscriptionsCommand extends Command
{
    const OPTION_REFERENCE_MEMBER = 'reference-member';

    private array $allSubscriptions;

    private array $memberSubscriptionVectors;

    private array $referenceVector;

    private int $totalSignificantSubscriptions;

    private InputInterface $input;

    private OutputInterface $output;

    public EntityManagerInterface $entityManager;

    public function configure()
    {
        $this->setName('app:recommend:subscriptions')
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
        $this->output = $output;

        $results = $this->findAllDistinctSubscriptions()
            ->buildReferenceVector()
            ->findClosestVectors()
        ;

        $distances = $this->computeDistancesBetweenVectors();
        $sortedDistances = $this->sortDistances($results, $distances);

        array_walk(
            $sortedDistances,
            function ($distance) {
                $this->output->writeln(
                    sprintf(
                        '%s %s',
                        $distance['member'],
                        $distance['distance']
                    )
                );
            }
        );
    }

    private function reduceMemberVector(array $initialVector)
    {
        $positions = array_values(explode(',', $initialVector));
        $positions = array_map('intval', $positions);
        $flippedPositions = array_flip($positions);

        return array_map(function ($subscription, $index) use ($flippedPositions) {
            if (!$subscription) {
                return null;
            }

            if (array_key_exists($index, $flippedPositions)) {
                // Result of normalization: $subscription / $subscription (!== 0)
                return 1;
            }

            return null;
        }, $this->allSubscriptions, array_keys($this->allSubscriptions));
    }

    /**
     * @throws Exception
     */
    private function findAllDistinctSubscriptions(): self
    {
        $allSubscriptions = $this->entityManager->getConnection()->executeQuery('
            SELECT array_agg(distinct subscription_id) all_subscription_ids FROM member_subscription
        ')->fetchAllAssociative()[0]['all_subscription_ids'];

        $allSubscriptions = explode(',', $allSubscriptions);
        $allSubscriptions = array_map(
            function ($subscription) {
                return (int) $subscription;
            },
            $allSubscriptions
        );
        array_unshift($allSubscriptions, null);

        $this->allSubscriptions = $allSubscriptions;

        return $this;
    }

    /**
     * @throws Exception
     */
    private function buildReferenceVector(): self
    {
        $query = <<<QUERY
            SELECT member_id,
            array_agg(
                FIND_IN_SET(
                    COALESCE(subscription_id, 0),
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
        $results = $statement->fetchAllAssociative();

        if (!array_key_exists(0, $results)) {
            throw new \LogicException('There should be subscriptions for the reference member');
        }

        $referenceVector = $results[0]['subscription_ids'];
        $this->totalSignificantSubscriptions = count(explode(',', $referenceVector));

        $this->referenceVector = $this->reduceMemberVector($referenceVector);

        return $this;
    }

    /**
     * @throws Exception
     */
    private function findClosestVectors(): array
    {
        $query = <<<QUERY
            SELECT                                               
            u.usr_twitter_username identifier,                   
            array_agg(                                        
              FIND_IN_SET(                                       
                subscription_id,                                 
                (SELECT group_concat(DISTINCT subscription_id)   
                 FROM member_subscription)                       
                )                                                
              ) subscription_ids,                                
            u.total_subscriptions                                
            FROM member_subscription s, weaving_user u           
            WHERE u.usr_id = s.member_id                         
            AND total_subscriptions BETWEEN :min AND :max              
            AND s.member_id in (                                 
               SELECT usr_id                                     
               FROM weaving_user                                 
               WHERE total_subscriptions > 0)                    
            AND total_subscriptions > 0                          
            GROUP BY member_id                                   
            LIMIT 50                                             
QUERY;
        $statement = $this->entityManager->getConnection()
            ->executeQuery(
                strtr(
                    $query,
                    [
                        ':min' => $this->totalSignificantSubscriptions * 0.5,
                        ':max' => $this->totalSignificantSubscriptions * 1
                    ]
                )
            );
        $results = $statement->fetchAllAssociative();

        $memberVectors = array_map(function ($record) {
            return $this->reduceMemberVector($record['subscription_ids']);
        }, $results);

        $this->memberSubscriptionVectors = $memberVectors;

        return $results;
    }

    private function computeDistancesBetweenVectors(): array
    {
        return array_map(
            function ($memberVector, $index) {
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
                    $this->referenceVector
                );

                return [
                    'distance' => sqrt(array_sum($powers)),
                    'vector' => $memberVector,
                    'vector_index' => $index,
                ];
            },
            $this->memberSubscriptionVectors,
            array_keys($this->memberSubscriptionVectors)
        );
    }

    private function sortDistances(array $results, array $distances): array
    {
        usort($distances, function ($vectorA, $vectorB) {
            if ($vectorA['distance'] === $vectorB['distance']) {
                return 0;
            }

            if ($vectorA['distance'] < $vectorB['distance']) {
                return -1;
            }

            return 1;
        });

        return array_map(
            function ($distance) use ($results) {
                $distance['member'] = $results[$distance['vector_index']]['identifier'];

                return $distance;
            },
            $distances
        );
    }
}
