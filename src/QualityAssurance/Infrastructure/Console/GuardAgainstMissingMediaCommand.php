<?php

namespace App\QualityAssurance\Infrastructure\Console;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Infrastructure\Entity\Legacy\Member as Member;
use App\QualityAssurance\Domain\Repository\TrendsRepositoryInterface;
use App\QualityAssurance\Infrastructure\Console\TweetNotFoundException as HttpTweetNotFoundException;
use App\Twitter\Domain\Curation\Curator\TweetCuratorInterface;
use App\Twitter\Domain\Http\Client\MemberProfileAwareHttpClientInterface;
use App\Twitter\Domain\Publication\Repository\NotFoundTweetRepositoryInterface;
use App\Twitter\Domain\Publication\Repository\TweetRepositoryInterface;
use App\Twitter\Domain\Publication\TweetInterface as NotFoundTweet;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GuardAgainstMissingMediaCommand extends Command {

    use LoggerTrait;

    const ARGUMENT_FILENAME = 'filename';
    private string $defaultAvatar = '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bnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgeG1wTU06RG9jdW1lbnRJRD0iZ2ltcDpkb2NpZDpnaW1wOjIwNzdkZTc4LWVhMmYtNGU5Yi05MDMzLTcwOWQzODRmODc4ZiIKICAgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDo5ZDc5NTk1NS1iZWM3LTQ5ZGUtOThkYS1mNTQ4ZDY3NGNmZjAiCiAgIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDphMTBiMTY1Zi0zMTU5LTRkMmEtOWQ1Yy1kZmM4NDEwZWY0ODUiCiAgIGRjOkZvcm1hdD0iaW1hZ2UvcG5nIgogICBHSU1QOkFQST0iMi4wIgogICBHSU1QOlBsYXRmb3JtPSJMaW51eCIKICAgR0lNUDpUaW1lU3RhbXA9IjE2NzI0MDExNTAwNDEwMDAiCiAgIEdJTVA6VmVyc2lvbj0iMi4xMC4zMiIKICAgdGlmZjpPcmllbnRhdGlvbj0iMSIKICAgeG1wOkNyZWF0b3JUb29sPSJHSU1QIDIuMTAiCiAgIHhtcDpNZXRhZGF0YURhdGU9IjIwMjI6MTI6MzBUMTI6NTI6MjkrMDE6MDAiCiAgIHhtcDpNb2RpZnlEYXRlPSIyMDIyOjEyOjMwVDEyOjUyOjI5KzAxOjAwIj4KICAgPHhtcE1NOkhpc3Rvcnk+CiAgICA8cmRmOlNlcT4KICAgICA8cmRmOmxpCiAgICAgIHN0RXZ0OmFjdGlvbj0ic2F2ZWQiCiAgICAgIHN0RXZ0OmNoYW5nZWQ9Ii8iCiAgICAgIHN0RXZ0Omluc3RhbmNlSUQ9InhtcC5paWQ6ZTdjMzZjNzktYTQ1ZS00YjIxLTkyMjAtM2E3ZjQzY2YwN2JlIgogICAgICBzdEV2dDpzb2Z0d2FyZUFnZW50PSJHaW1wIDIuMTAgKExpbnV4KSIKICAgICAgc3RFdnQ6d2hlbj0iMjAyMi0xMi0zMFQxMjo0NjoyMyswMTowMCIvPgogICAgIDxyZGY6bGkKICAgICAgc3RFdnQ6YWN0aW9uPSJzYXZlZCIKICAgICAgc3RFdnQ6Y2hhbmdlZD0iLyIKICAgICAgc3RFdnQ6aW5zdGFuY2VJRD0ieG1wLmlpZDo5OGIzZmM5Yi0yMzM5LTRlY2YtOGNlMS03ZjE3OWY4NTI4MTIiCiAgICAgIHN0RXZ0OnNvZnR3YXJlQWdlbnQ9IkdpbXAgMi4xMCAoTGludXgpIgogICAgICBzdEV2dDp3aGVuPSIyMDIyLTEyLTMwVDEyOjUyOjMwKzAxOjAwIi8+CiAgICA8L3JkZjpTZXE+CiAgIDwveG1wTU06SGlzdG9yeT4KICA8L3JkZjpEZXNjcmlwdGlvbj4KIDwvcmRmOlJERj4KPC94OnhtcG1ldGE+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAKPD94cGFja2V0IGVuZD0idyI/PvMpRWUAAAAJcEhZcwAACxMAAAsTAQCanBgAAAAHdElNRQfmDB4LNB40j77nAAAC3ElEQVRYw+3X20/TUBgA8LZrC+10Fy4SiQsLAgLBGJ3RGDDGJxP/XiMvKjowwobcO5Bt3Rg6uq1ru7XdunY9xwcTJcbNtuwAD/sem9Pkl/Y73wVPcjx2nYLArln0QX1QH9QHXXWQF3xf1bSqJOuNJgCApulwMDA8FPb5fFcAqojVxM5e4awMITz/nGUGYwtzczNTpCcW7q2XcUfH8eT2X5TzMX5r5NWLJYYZvIwcOs7ynxJbXTQYhhXL4vJK3Gq3kYPqqvZx/auTk4Io7RykkIN2ucO2bTs8vHVwpOsNhCCj1Uplc87P2wDwJwWEIKFcsW3g6pXsySlCUEWsuv3FQlU2LQsVSFJqbkEAAL3RQAWqabqHMtFsGkhAEEKjZXoAmaaFCgQA8ACCECAB4ThOkl7aE+GmqbkDBfx+DyCWYVAl9e2xUdfjhM8XDNxEBYpG7rgFTUcjNEWhAo0OD83djTo/T5G+R/fn0TbXxSexyci4k5MDNPX65fNgIIB8QAMQnhS+p9KZU6Hyz0IQuOG/NzkxPzPlZ9lLmhh/hWVZeqPRNFq2bUMICYIYoGmWYRhmEMdxjzM1jmHQK4iiqFAwGAr216BOnUTVdElW6ppqGCaAgKYoP8uGg4FwOESR5OWBmoaRyeVTab6q1DvcdnI6GpmdmhwbHXGbTPgmxzvPoXa7zX1LJ3Y5h+vExPjYs9jDoXAICUiu1d6tfqlIirskJYjF2IOF2RmHn8rpLysKpbcra66G0d8T42pyW5KVpaePnazYjm7ZWan85n3cg+bPppvJxdeTTsap/4Pqqrq8suZ8F+sUh9n89j53UZANwIfPG4Zp9qTGJPZSRaF0IdBxhi+WxV4VPQhhfGOz+8fuBjIta2Nnv7eFWKqp2VzeI4jPFxpGq+fNYevgsEt2dwOl0lkU3Uqua+XOG3BHkKppJVFC1EELP4quQZWqBDEMGUhwDZJkBZkHExWlUzf8CUKvaG2kXhSBAAAAAElFTkSuQmCC';

    const COMMAND_NAME = 'app:guard-against-missing-media';

    private InputInterface $input;
    private OutputInterface $output;

    private array $cachedMembers = [];

    private EntityManagerInterface $entityManager;

    public function setEntityManager(EntityManagerInterface $entityManager): self
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    private MemberProfileAwareHttpClientInterface $memberProfileHttpClient;

    public function setMemberProfileAwareHttpClient(MemberProfileAwareHttpClientInterface $memberProfileHttpClient): self
    {
        $this->memberProfileHttpClient = $memberProfileHttpClient;

        return $this;
    }

    private NotFoundTweetRepositoryInterface $notFoundTweetRepository;

    public function setNotFoundTweetRepository(NotFoundTweetRepositoryInterface $repository)
    {
        $this->notFoundTweetRepository = $repository;

        return $this;
    }

    private ServiceEntityRepositoryInterface $tweetRepository;

    public function setTweetRepository(TweetRepositoryInterface $repository)
    {
        $this->tweetRepository = $repository;

        return $this;
    }

    private string $resourcesDir;

    public function setResourcesDir($resourcesDir): self
    {
        $this->resourcesDir = $resourcesDir;

        return $this;
    }

    private TrendsRepositoryInterface $trendsRepository;

    public function setTrendsRepository(TrendsRepositoryInterface $trendsRepository): void
    {
        $this->trendsRepository = $trendsRepository;
    }

    private TweetCuratorInterface $tweetCurator;

    public function setTweetCurator(TweetCuratorInterface $tweetCurator): self
    {
        $this->tweetCurator = $tweetCurator;

        return $this;
    }

    public function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->addArgument(
                self::ARGUMENT_FILENAME,
                description: sprintf(
                    'CSV filename containing Tweets ready for processing available from %s',
                    $this->resourcesDir
                )
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $filePath = sprintf(
            '%s/%s',
            $this->resourcesDir,
            $this->input->getArgument(self::ARGUMENT_FILENAME)
        );

        $tweets = $this->makeTweets($filePath);
        $tweetsPartition = array_chunk($tweets, 1000, true);

        array_walk(
            $tweetsPartition,
            function ($partition, $index) use (&$tweetsPartition) {
                try {
                    $processedTweets = array_map(
                        [$this, 'processTweet'],
                        $partition
                    );
                    array_walk(
                        $processedTweets,
                        [$this, 'updateTrend']
                    );

                    // Freeing memory
                    $tweetsPartition[$index] = [];

                    $this->info(sprintf('Processed successfully partition #%d', $index));
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
        );

        return self::SUCCESS;
    }

    /**
     * @throws Exception
     */
    public function makeTweets(string $filePath): array
    {
        $lines = [];
        $contents = file_get_contents($filePath);
        $size = (4 * strlen($contents)) / 1024 / 1024;
        ini_set('memory_limit', sprintf('%dM', $size * 2));

        preg_match_all('#(^[0-9][0-9][0-9][0-9][0-9].*,(?:False|True))#m', $contents, $lines);

        return array_reduce($lines[1], function ($carry, $item) {
            if ($carry === null) {
                return [];
            }

            preg_match(
                '#(?<id>\d+),"(?!>")(?<hash>[^,]+)","(?!>")(?<username>[^,]+)","(?!>")(?<name>[^"]+)",' .
                '"(?!>")(?<text>.+)","(?!>")(?<avatarURL>http(s)?://[^,]+)","(?!>")(?<userToken>[-a-z0-9A-Z]+)",' .
                '"(?!>")(?<statusId>\d+)","(?<rawDocument>.+\})",(?<isStarred>.+),' .
                '(?<isIndexed>.+),"(?!>")(?<createdAt>.+)","?(?<updatedAt>(?!>)' .
                '(:?[^"]+)|NULL)"?,(?<isPublished>.+)' .
                '#sm',
                $item,
                $matches
            );

            if (!array_key_exists(1, $matches)) {
                throw new \InvalidArgumentException(sprintf('Invalid item (%s)', var_export($item, true)));
            }

            $updatedAt = $matches['updatedAt'] === 'NULL'
                ? new DateTimeImmutable($matches['createdAt'])
                : new DateTimeImmutable($matches['updatedAt'])
            ;

            $tweet = new Tweet(
                $matches['id'],
                $matches['hash'],
                $matches['username'],
                $matches['name'],
                $matches['text'],
                $matches['avatarURL'],
                $matches['statusId'],
                str_replace('""', '"', $matches['rawDocument']),
                new DateTimeImmutable($matches['createdAt']),
                $updatedAt,
                boolval($matches['isStarred']),
                boolval($matches['isIndexed']),
                boolval(trim($matches['isPublished']))
            );

            $carry[] = $tweet;

            return $carry;
        });
    }

    private function fetchExtendedEntities(TweetInterface $tweet): TweetInterface
    {
        try {
            $message = sprintf('About to collect tweet having id %s', $tweet->statusId);
            $this->info($message);

            $rawTweetDocument = (array)$this->tweetCurator->collectSingleTweet($tweet->statusId);

            return $tweet->overrideProperties([
                    'raw_document' => json_encode(
                        $rawTweetDocument,
                        JSON_OBJECT_AS_ARRAY
                    )]
            );
        } catch (TweetNotFoundException) {
            $errorMessage = sprintf('Could not find tweet having id %s', $tweet->statusId);
            $this->error($errorMessage);

            $notFoundTweet = $this->tweetRepository->findOneBy(['statusId' => $tweet->tweetId()]);

            if ($notFoundTweet instanceof NotFoundTweet) {
                $this->notFoundTweetRepository->markStatusAsNotFound($notFoundTweet);

                $this->entityManager->persist($notFoundTweet);
                $this->entityManager->flush($notFoundTweet);
            }

            $tweet->markAsDeleted();

            return $tweet;
        }
    }

    /**
     * @throws \App\QualityAssurance\Infrastructure\Console\AvatarNotFoundException
     */
    public function extractTweetPublisherAvatar(TweetInterface $tweet): string
    {
        $avatarUrl = $tweet->rawDocument['user']['profile_image_url_https'];

        try {
            return base64_encode($this->getMedia($avatarUrl));
        } catch (\Exception) {
            AvatarNotFoundException::throws($tweet->tweetId());
        }
    }

    /**
     * @throws \ErrorException
     */
    public function extractTweetMedia(TweetInterface $tweet): string
    {
        try {
            return base64_encode($this->getMedia($tweet->smallMediaURL()));
        } catch (\ErrorException $e) {
            if (substr_count($e->getMessage(), '404') === 0) {
                throw $e;
            }

            try {
                $tweet = $this->fetchExtendedEntities($tweet);

                if ($tweet->hasBeenDeleted()) {
                    HttpTweetNotFoundException::throws($tweet);
                }

                return base64_encode($tweet->smallMediaURL());
            } catch (\Exception $e) {
                if ($e instanceof HttpTweetNotFoundException) {
                    throw $e;
                }

                $this->error(
                    sprintf(
                        'Could not fetch extended entities for tweet %s (error message: %s)',
                        $tweet->statusId,
                        $e->getMessage()
                    )
                );
            }
        } catch (\Exception $e) {
            $this->error(
                sprintf(
                    'Could not fetch media for tweet %s (error message: %s)',
                    $tweet->statusId,
                    $e->getMessage()
                )
            );
        }

        return '';
    }

    public function extractAvatarDataURI(MemberInterface $member, TweetInterface $tweet): mixed
    {
        $rawMemberDocument = $member->rawDocument();

        if (!array_key_exists('profile_image_url_https', $rawMemberDocument)) {
            return $this->defaultAvatar;
        }

        try {
            return base64_encode($this->getMedia($rawMemberDocument['profile_image_url_https']));
        } catch (\Exception $e) {
            $this->error(
                sprintf(
                    'Could not fetch avatar for tweet having "%s" id (error message: %s)',
                    $tweet->statusId,
                    $e->getMessage()
                )
            );
        }

        return $this->defaultAvatar;
    }

    public function refreshProfileImageURL(MemberInterface $member, TweetInterface $tweet): TweetInterface
    {
        $message = sprintf('About to collect member having id %s', $member->twitterId());
        $this->info($message);

        if (!array_key_exists($member->twitterId(), $this->cachedMembers)) {
            $member = $this->memberProfileHttpClient->ensureMemberProfileIsUpToDate(
                $member,
                $member->twitterScreenName()
            );
            $this->cachedMembers[$member->twitterId()] = $member;
        } else {
            $member = $this->cachedMembers[$member->twitterId()];
        }

        $rawMemberDocument = $member->rawDocument();

        if (!array_key_exists('profile_image_url_https', $rawMemberDocument)) {
            return $tweet;
        }

        $rawTweetDocument = $tweet->rawDocument();
        $rawTweetDocument['user']['profile_image_url_https'] = $rawMemberDocument['profile_image_url_https'];

        return $tweet->overrideProperties([
            'raw_document' => json_encode($rawTweetDocument, JSON_OBJECT_AS_ARRAY)]
        );
    }

    public function refreshExtendedEntities(TweetInterface $tweet): TweetInterface
    {
        if (array_key_exists('extended_entities', $tweet->rawDocument)) {
            return $tweet;
        }

        return $this->fetchExtendedEntities($tweet);
    }

    public function getMedia(string $avatarUrl): string
    {
        $message = sprintf('About to get media located at %s', $avatarUrl);
        $this->info($message);

        $contents = file_get_contents($avatarUrl);

        if ($contents === false) {
            throw new \InvalidArgumentException('Could not get media.');
        }

        return $contents;
    }

    public function info(string $message): void
    {
        $this->output->writeln('<info>'.$message.'</info>');
        $this->logger->info($message);
    }

    public function error(string $errorMessage): void
    {
        $this->output->writeln('<error>' . $errorMessage . '</error>');
        $this->logger->error($errorMessage);
    }

    private function processTweet(TweetInterface $tweet): TweetInterface
    {
        try {
            $overrides['avatar_data_uri'] = $this->extractTweetPublisherAvatar($tweet);
        } catch (AvatarNotFoundException) {
            /** @var Member $member */
            $member = $this->memberProfileHttpClient->getMemberByIdentity(
                new MemberIdentity(
                    $tweet->username,
                    $tweet->rawDocument['user']['id_str']
                )
            );
            $tweet = $this->refreshProfileImageURL($member, $tweet);
            $overrides['avatar_data_uri'] = $this->extractAvatarDataURI($member, $tweet);
        }

        $tweet = $this->refreshExtendedEntities($tweet);

        if (!array_key_exists('extended_entities', $tweet->rawDocument)) {
            return $tweet->overrideProperties($overrides);
        }

        try {
            $tweetMedia = $this->extractTweetMedia($tweet);
            if (strlen($tweetMedia) > 0) {
                $overrides['media_data_uri'] = $tweetMedia;

                return $tweet->overrideProperties($overrides);
            }
        }  catch (HttpTweetNotFoundException $e) {
            return $e->tweet;
        }

        return $tweet;
    }

    public function updateTrend(TweetInterface $tweet)
    {
        if ($tweet->hasBeenDeleted()) {
            $this->trendsRepository->removeTweetFromTrends(
                $tweet->tweetId(),
                $tweet->createdAt()
            );

            return;
        }

        $this->trendsRepository->updateTweetDocument(
            $tweet->tweetId(),
            $tweet->createdAt(),
            json_encode($tweet->rawDocument())
        );
    }
}