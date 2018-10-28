<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Command;

use App\Aggregate\Entity\SavedSearch;
use App\Aggregate\Repository\SavedSearchRepository;
use App\Aggregate\Repository\SearchMatchingStatusRepository;
use App\Conversation\Producer\MemberAwareTrait;
use App\Operation\OperationClock;

use App\Status\LikedStatusCollectionAwareInterface;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Translation\TranslatorInterface;

use WeavingTheWeb\Bundle\AmqpBundle\Exception\InvalidListNameException;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Token;

use WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException;

use WTW\UserBundle\Entity\User;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProduceListsMembersCommand extends AggregateAwareCommand
{
    use MemberAwareTrait;

    /**
     * @var \OldSound\RabbitMqBundle\RabbitMq\Producer
     */
    private $producer;

    /**
     * @var \OldSound\RabbitMqBundle\RabbitMq\Producer
     */
    private $likesMessagesProducer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $listRestriction;

    /**
     * @var string
     */
    private $queryRestriction;

    /**
     * @var array[string]
     */
    private $listCollectionRestriction;

    /**
     * @var string
     */
    private $screenName;

    /**
     * @var bool
     */
    private $givePriorityToAggregate = false;

    /**
     * @var string
     */
    private $before = null;

    /**
     * @var OperationClock
     */
    public $operationClock;

    /**
     * @var SavedSearchRepository
     */
    public $savedSearchRepository;

    /**
     * @var SearchMatchingStatusRepository
     */
    public $searchMatchingStatusRepository;

    public function configure()
    {
        $this->setName('weaving_the_web:amqp:produce:lists_members')
            ->setDescription('Produce a message to get lists members status')
            ->addOption(
            'oauth_token',
            null,
            InputOption::VALUE_OPTIONAL,
            'A token is required'
        )->addOption(
            'oauth_secret',
            null,
            InputOption::VALUE_OPTIONAL,
            'A secret is required'
        )->addOption(
            'screen_name',
            null,
            InputOption::VALUE_REQUIRED,
            'The screen name of a user'
        )->addOption(
            'list',
            null,
            InputOption::VALUE_OPTIONAL,
            'A list to which production is restricted to'
        )->addOption(
            'lists',
                'l',
            InputOption::VALUE_OPTIONAL,
            'List to which publication of messages is restricted to'
        )
        ->addOption(
            'priority_to_aggregates',
            'pa',
            InputOption::VALUE_NONE,
            'Publish messages the priority queue for visible aggregates'
        )
        ->addOption(
            'query_restriction',
            'qr',
            InputOption::VALUE_OPTIONAL,
            'Query to search statuses against'
        )->addOption(
            'before',
            null,
            InputOption::VALUE_OPTIONAL,
            'Date before which statuses should have been created'
        )->setAliases(array('wtw:amqp:tw:prd:lm'));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null
     * @throws InvalidListNameException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->setOptionsFromInput();

        if ($this->operationClock->shouldSkipOperation()
            && !$this->givePriorityToAggregate
            && !$this->queryRestriction
        ) {
            return self::RETURN_STATUS_SUCCESS;
        }

        $this->setUpDependencies();

        if ($this->queryRestriction) {
            $this->productSearchStatusesMessages();

            return self::RETURN_STATUS_SUCCESS;
        }

        return $this->productListsMessages();
    }

    /**
     * @param $members
     * @param $messageBody
     * @param $list
     * @return int
     * @throws \Exception
     */
    protected function publishMembersScreenNames($members, $messageBody, $list)
    {
        $publishedMessages = 0;

        foreach ($members->users as $friend) {
            try {
                $member = $this->getMessageUser($friend);

                if ($member->isAWhisperer()) {
                    $message = sprintf('Ignoring whisperer with screen name "%s"', $friend->screen_name);
                    $this->logger->info($message);

                    continue;
                }

                if ($member->isProtected()) {
                    $message = sprintf('Ignoring protected member with screen name "%s"', $friend->screen_name);
                    $this->logger->info($message);

                    continue;
                }

                if ($member->isSuspended()) {
                    $message = sprintf('Ignoring suspended member with screen name "%s"', $friend->screen_name);
                    $this->logger->info($message);

                    continue;
                }

                $messageBody['screen_name'] = $member->getTwitterUsername();

                $aggregate = $this->getListAggregateByName(
                    $messageBody['screen_name'],
                    $list->name,
                    $list->id_str
                );
                $messageBody['aggregate_id'] = $aggregate->getId();

                if ($this->before) {
                    $messageBody['before'] = $this->before;
                }

                $this->producer->setContentType('application/json');
                $this->producer->publish(serialize(json_encode($messageBody)));

                if ($this->likesMessagesProducer instanceof Producer) {
                    $this->likesMessagesProducer->setContentType('application/json');
                    $messageBody[LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES] = true;
                    $this->likesMessagesProducer->publish(serialize(json_encode($messageBody)));
                    $messageBody[LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES] = false;
                }

                $publishedMessages++;
            } catch (\Exception $exception) {
                if ($this->shouldBreakPublication($exception)) {
                    break;
                } elseif ($this->shouldContinuePublication($exception)) {
                    continue;
                } else {
                    throw $exception;
                }
            }
        }

        return $publishedMessages;
    }

    /**
     * @param      $twitterUser
     * @param      $friend
     * @param bool $protected
     * @param bool $suspended
     * @return User
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function makeUser($twitterUser, $friend, $protected = false, $suspended = false)
    {
        $message = '[publishing new message produced for "' . ($twitterUser->screen_name) . '"]';
        $this->logger->info($message);

        $user = $this->userRepository->make($friend->id, $twitterUser->screen_name, $protected, $suspended);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * @param $ownerships
     * @param $listRestriction
     * @return bool
     */
    private function targetListHasBeenFound($ownerships, string $listRestriction): bool
    {
        $listNames = $this->mapOwnershipsLists($ownerships);

        return in_array($listRestriction, $listNames, true);
    }

    /**
     * @param        $ownerships
     * @param string $listRestriction
     * @return bool
     */
    private function targetListHasNotBeenFound($ownerships, string $listRestriction): bool {
        return ! $this->targetListHasBeenFound($ownerships, $listRestriction);
    }

    /**
     * @param $ownerships
     * @return array
     */
    private function mapOwnershipsLists($ownerships): array
    {
        return array_map(function ($list) {
            $list = (array) $list;

            return $list['name'];
        }, (array)$ownerships->lists);
    }

    /**
     * @param                 $ownerships
     * @return \API|mixed|object|\stdClass
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     */
    private function findNextBatchOfListOwnerships($ownerships)
    {
        $previousCursor = -1;

        if (is_null($this->listRestriction)) {
            return $this->accessor->getUserOwnerships($this->screenName, $ownerships->next_cursor);
        }

        while ($this->targetListHasNotBeenFound($ownerships, $this->listRestriction)) {
            $ownerships = $this->accessor->getUserOwnerships($this->screenName, $ownerships->next_cursor);

            if (!isset($ownerships->next_cursor) || $previousCursor === $ownerships->next_cursor) {
                $this->output->write(sprintf(
                    'No more pages of members lists to be processed. ',
                    'Does the Twitter API access token used belong to "%s"?',
                    $this->screenName
                ));

                break;
            }

            $previousCursor = $ownerships->next_cursor;
        }

        return $ownerships;
    }

    private function setUpDependencies()
    {
        $tokens = $this->getTokensFromInput();
        $this->setupAccessor($tokens);

        $this->setupAggregateRepository();
        $this->setUpLogger();

        $this->producer = $this->getContainer()
            ->get('old_sound_rabbit_mq.weaving_the_web_amqp.twitter.user_status_producer');

        if (!is_null($this->listRestriction) && $this->listRestriction == 'news :: France') {
            $this->producer = $this->getContainer()
                ->get('old_sound_rabbit_mq.weaving_the_web_amqp.twitter.news_status_producer');
        }

        if ((!is_null($this->listRestriction) || !is_null($this->listCollectionRestriction)) &&
            $this->givePriorityToAggregate
        ) {
            $this->producer = $this->getContainer()
                ->get('old_sound_rabbit_mq.weaving_the_web_amqp.twitter.aggregates_status_producer');

            $this->likesMessagesProducer = $this->getContainer()
                ->get('old_sound_rabbit_mq.weaving_the_web_amqp.producer.aggregates_likes_producer');
        }

        if ($this->queryRestriction) {
            $this->producer = $this->getContainer()
                ->get('old_sound_rabbit_mq.weaving_the_web_amqp.producer.search_matching_statuses_producer');
        }

        $this->translator = $this->getContainer()->get('translator');
    }

    /**
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function updateAccessToken()
    {
        $tokens = $this->getTokensFromInput();

        /** @var Token $token */
        $token = $this->findTokenOtherThan($tokens['token']);

        $oauthTokens = [
            'token' => $token->getOauthToken(),
            'secret' => $token->getOauthTokenSecret(),
            'consumer_token' => $token->consumerKey,
            'consumer_secret' => $token->consumerSecret
        ];
        $this->setupAccessor($oauthTokens);

        return $oauthTokens;
    }

    /**
     * @param $ownerships
     * @return \API|mixed|object|\stdClass
     * @throws InvalidListNameException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     */
    private function guardAgainstInvalidListName($ownerships)
    {
        if ($this->listRestriction) {
            $ownerships = $this->findNextBatchOfListOwnerships($ownerships);

            if ($this->targetListHasNotBeenFound($ownerships, $this->listRestriction)) {
                $ownerships = $this->guardAgainstInvalidToken($ownerships);
            }

            if ($this->targetListHasNotBeenFound($ownerships, $this->listRestriction)) {
                $message = sprintf(
                    'Invalid list name ("%s"). Could not be found',
                    $this->listRestriction
                );
                $this->output->writeln($message);

                throw new InvalidListNameException($message);
            }
        }

        return $ownerships;
    }

    private function setOptionsFromInput(): void
    {
        $this->screenName = $this->input->getOption('screen_name');

        $this->listRestriction = null;
        if ($this->input->hasOption('list') && !is_null($this->input->getOption('list'))) {
            $this->listRestriction = $this->input->getOption('list');
        }

        $this->listCollectionRestriction = [];
        if ($this->input->hasOption('lists') && !is_null($this->input->getOption('lists'))) {
            $this->listCollectionRestriction = explode(',', $this->input->getOption('lists'));
            $restiction = (object)[];
            $restiction->list = [];
            array_walk(
                $this->listCollectionRestriction,
                function($list) use ($restiction) {
                    $restiction->list[$list] = $list;
                }
            );
            $this->listCollectionRestriction = $restiction->list;
        }

        if ($this->input->hasOption('before') && !is_null($this->input->getOption('before'))) {
            $this->before = $this->input->getOption('before');
        }

        if ($this->input->hasOption('priority_to_aggregates') &&
            $this->input->getOption('priority_to_aggregates')) {
            $this->givePriorityToAggregate = true;
        }

        if ($this->input->hasOption('query_restriction') &&
            $this->input->getOption('query_restriction')) {
            $this->queryRestriction = $this->input->getOption('query_restriction');
        }
    }

    /**
     * @param $ownerships
     * @return \API|mixed|object|\stdClass
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     */
    private function guardAgainstInvalidToken($ownerships)
    {
        $this->updateAccessToken();
        $ownerships->next_cursor = -1;

        return $this->findNextBatchOfListOwnerships($ownerships);
    }

    /**
     * @return int
     * @throws InvalidListNameException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     */
    private function productListsMessages()
    {
        try {
            $ownerships = $this->accessor->getUserOwnerships($this->screenName);
            $messageBody = $this->getTokensFromInput();
        } catch (UnavailableResourceException $exception) {
            $messageBody = $this->updateAccessToken();
            $ownerships = $this->accessor->getUserOwnerships($this->screenName);
        }

        $ownerships = $this->guardAgainstInvalidListName($ownerships);

        $doNotApplyListRestriction = is_null($this->listRestriction) &&
            count($this->listCollectionRestriction) === 0;
        if ($doNotApplyListRestriction && count($ownerships->lists) === 0) {
            $ownerships = $this->guardAgainstInvalidToken($ownerships);
        }

        $shouldIncludeOwner = true;

        foreach ($ownerships->lists as $list) {
            $shouldIncludeOwner = $this->processMemberList(
                $doNotApplyListRestriction,
                $list,
                $shouldIncludeOwner,
                $messageBody
            );
        }

        return self::RETURN_STATUS_SUCCESS;
    }

    /**
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     */
    private function productSearchStatusesMessages()
    {
        $savedSearch = $this->savedSearchRepository
            ->findOneBy(['searchQuery' => $this->queryRestriction]);

        if (!($savedSearch instanceof SavedSearch)) {
            $response = $this->accessor->saveSearch($this->queryRestriction);
            $savedSearch = $this->savedSearchRepository->make($response);
            $this->savedSearchRepository->save($savedSearch);
        }

        $results = $this->accessor->search($savedSearch->searchQuery);

        $this->searchMatchingStatusRepository->saveSearchMatchingStatus(
            $savedSearch,
            $results->statuses,
            $this->accessor->userToken
        );
    }

    /**
     * @param $doNotApplyListRestriction
     * @param $list
     * @param $shouldIncludeOwner
     * @param $messageBody
     * @return bool
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     */
    private function processMemberList(
        $doNotApplyListRestriction,
        $list,
        $shouldIncludeOwner,
        $messageBody
    ) {
        if ($doNotApplyListRestriction ||
            $list->name === $this->listRestriction ||
            array_key_exists($list->name, $this->listCollectionRestriction)
        ) {
            $members = $this->accessor->getListMembers($list->id);

            if ($shouldIncludeOwner) {
                $additionalMember = $this->accessor->showUser($this->screenName);
                array_unshift($members->users, $additionalMember);
                $shouldIncludeOwner = false;
            }

            if (!is_object($members) || !isset($members->users) || count($members->users) === 0) {
                $this->logger->info(sprintf('List "%s" has no members', $list->name));

                return $shouldIncludeOwner;
            }

            if (count($members->users) > 0) {
                $this->logger->info(
                    sprintf(
                        'About to publish messages for members in list "%s"',
                        $list->name
                    )
                );
            }

            $publishedMessages = $this->publishMembersScreenNames($members, $messageBody, $list);

            // Reset accessor tokens in case they've been updated to find member usernames with alternative tokens
            // Members lists can only be accessed by authenticated users owning the lists
            // See also https://dev.twitter.com/rest/reference/get/lists/ownerships
            $this->updateAccessToken();

            $this->output->writeln($this->translator->trans('amqp.production.list_members.success', [
                '{{ count }}' => $publishedMessages,
                '{{ list }}' => $list->name,
            ]));
        }

        return $shouldIncludeOwner;
    }
}
