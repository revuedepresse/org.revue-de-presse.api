<?php
declare (strict_types=1);

namespace App\Trends\Infrastructure\Repository;

use App\Trends\Domain\Repository\PopularPublicationRepositoryInterface;
use App\Trends\Domain\Repository\SearchParamsInterface;
use DateTimeInterface;
use Psr\Log\LoggerInterface;

class PopularPublicationRepository implements PopularPublicationRepositoryInterface
{
    private string $projectDir;

    private LoggerInterface $logger;

    public function __construct(
        string $projectDir,
        LoggerInterface $logger
    ) {
        $this->projectDir = $projectDir;
        $this->logger = $logger;
    }

    private function loadHighlightsSnapshot(string $searchDate): array
    {
        $todayHighlights = $this->projectDir.'/src/Bluesky/Resources/'.$searchDate.'.json';

        if (file_exists($todayHighlights)) {
            return [
                $searchDate => file_get_contents($todayHighlights)
            ];
        }

        return [
            $searchDate => json_encode([])
        ];
    }

    private function getHighlightsSnapshot(
        DateTimeInterface $date,
        bool $includeRetweets = false,
        bool $curatingHighlightsFromDistinctSources = false
    ): array {
        $searchDate = $date->format('Y-m-d');
        $database = $this->loadHighlightsSnapshot($searchDate);
        $this->logger->info(sprintf('About to access highlights snapshot path: "%s"', $searchDate));

        return $database;
    }

    /**
     * @throws \App\Conversation\Exception\InvalidStatusException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \JsonException
     * @throws \Safe\Exceptions\FilesystemException
     */
    public function findBy(SearchParamsInterface $searchParams): array {
        $searchDate = $searchParams->getParams()['startDate'];

        $snapshot = $this->getHighlightsSnapshot(
            $searchDate,
            $searchParams->getParams()['includeRetweets'],
            $searchParams->curatingHighlightsFromDistinctSources(),
         );

        $formattedSearchDate = $searchDate->format('Y-m-d');

        if (array_key_exists($formattedSearchDate, $snapshot)) {
            $highlights = json_decode($snapshot[$formattedSearchDate], true);
        } else {
            $highlights = [];
        }
        
        $publications = isset($highlights['statuses']) 
            ? $highlights['statuses'] 
            : $highlights;

        return [
            'aggregates' => [],
            'statuses' => array_map(
                function ($status) {
                    if (array_key_exists('publication_id', $status)) {
                        $parts = explode('/', $status['publication_id']);
                        
                        $status['url'] = implode([
                            'https://bsky.app/profile/',
                            $status['screen_name'],
                            '/post/',
                            $parts[4],
                        ]);
                    } else {
                        $publication = $status;
                        if (array_key_exists('status', $status)) {
                            $publication = $status['status'];
                        }
                        $originalDocument = [
                            'full_text' => $publication['text'],
                        ];
                        if (array_key_exists('original_document', $publication)) {
                            $originalDocument = json_decode(
                                $publication['original_document'],
                                true
                            );
                        }

                        $status = [
                            'date' => $status['publicationDateTime'],
                            'screen_name' => $publication['username'],
                            'reposts' => $publication['retweet_count'],
                            'likes' => $publication['favorite_count'],
                            'text' => $originalDocument['full_text'],
                            'publication_id' => $publication['status_id'],
                            'avatar_url' => $publication['avatar_url'],
                            'url' => implode(
                                '/',
                                [
                                    'https://twitter.com',
                                    $publication['username'],
                                    'status',
                                    $publication['status_id']
                                ],
                            )
                        ];
                    }

                    $status['status'] = $status;
                    
                    return $status;
                },
                $publications
            ),
            'version' => 'v3.7.1',
        ];
    }
}
