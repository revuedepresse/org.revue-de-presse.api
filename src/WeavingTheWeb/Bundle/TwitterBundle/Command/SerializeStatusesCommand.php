<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputOption;

/**
 * Statuses serialization command
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SerializeStatusesCommand extends ContainerAwareCommand
{
    /**
     * @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Reader\FeedReader $feedReader
     */
    protected $feedReader;

    /**
     * Configures executable commands
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('tw:api:twitter:statuses:serialize')
            ->addOption(
                'oauth_token',
                null,
                InputOption::VALUE_OPTIONAL,
                'OAuth token'
            )
            ->addOption(
                'oauth_secret',
                null,
                InputOption::VALUE_OPTIONAL,
                'OAuth secret token'
            )
            ->addOption(
                'screen_name',
                null,
                InputOption::VALUE_REQUIRED,
                'Screen name'
            )
            ->addOption(
                'greedy',
                null,
                InputOption::VALUE_REQUIRED,
                'Try saving all statuses provided rate limits of Twitter API consumption and user statuses count',
                false
            )
            ->addOption(
                'count',
                null,
                InputOption::VALUE_REQUIRED,
                'Results count',
                200
            )
            ->addOption(
                'page',
                null,
                InputOption::VALUE_REQUIRED,
                'Page index',
                1
            )
            ->setDescription('Serialize response returned when accessing user statuses endpoint from twitter api')
            ->setAliases(array('wtw:api:tw:statuses'));
    }

    /**
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oauthTokens = $this->getOauthTokens($input);
        $options = [
            'oauth' => $oauthTokens['token'],
            'count' => $input->getOption('count'),
            'page' => $input->getOption('page'),
            'screen_name' => $input->getOption('screen_name')
        ];
        $this->setUpFeedReader($oauthTokens);


        $apiRateLimitReached = $this->isApiRateLimitReached();
        $remainingUserTweets = $this->remainingTweetsForUser($options);

        while ($remainingUserTweets && !$apiRateLimitReached) {

            $this->persistStatuses($options);

            if (!$input->getOption('greedy')) {
                break;
            }

            $status = $this->findNextMaxStatus($options);
            if ((count($status) === 1) && array_key_exists('statusId', $status)) {
                $options['max_id'] = $status['statusId'] - 1;
            }
            $apiRateLimitReached = $this->isApiRateLimitReached();
            $remainingUserTweets = $this->remainingTweetsForUser($options);
        }

        /**
         * @var \Symfony\Component\Translation\Translator $translator
         */
        $translator = $this->getContainer()->get('translator');
        $output->writeln($translator->trans('twitter.statuses.persistence.success'));
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function getOauthTokens(InputInterface $input)
    {
        if ($input->hasOption('oauth_token') && !is_null($input->getOption('oauth_token'))) {
            $token = $input->getOption('oauth_token');
        } else {
            $token = $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_token.default');
        }
        if ($input->hasOption('oauth_secret') && !is_null($input->getOption('oauth_secret'))) {
            $secret = $input->getOption('oauth_secret');
        } else {
            $secret = $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_secret.default');
        }

        return array('token' => $token, 'secret' => $secret);
    }

    /**
     * @param $oauthTokens
     */
    protected function setUpFeedReader($oauthTokens)
    {
        /**
         * @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Reader\FeedReader $feedReader
         */
        $this->feedReader = $this->getContainer()->get('weaving_the_web_legacy_provider.feed_reader');
        $this->feedReader->setUserToken($oauthTokens['token']);
        $this->feedReader->setUserSecret($oauthTokens['secret']);
    }

    /**
     * @param $options
     * @return bool
     */
    protected function remainingTweetsForUser($options)
    {
        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository
         */
        $userStreamRepository = $this->getContainer()->get('weaving_the_web_api.repository.user_stream');
        $countQueryBuilder = $userStreamRepository->createQueryBuilder('u');
        $countQueryBuilder->select('count(u.id) as count_')
            ->where('u.identifier = :oauth');
        $countQueryBuilder->setParameter('oauth', $options['oauth']);
        $count = $countQueryBuilder->getQuery()->getSingleScalarResult();
        $user = $this->feedReader->showUser($options['screen_name']);

        // Introduces 10 percent margin to be safe
        return $count < ($user->statuses_count - $user->statuses_count / 10);
    }

    /**
     * @param $options
     * @return \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository
     */
    protected function findNextMaxStatus($options)
    {
        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository
         */
        $userStreamRepository = $this->getContainer()->get('weaving_the_web_api.repository.user_stream');

        $subqueryBuilder = $userStreamRepository->createQueryBuilder('u');
        $subqueryBuilder->select('min(u.statusId) as since_id')
            ->where('u.identifier = :oauth');

        $queryBuilder = $userStreamRepository->createQueryBuilder('s');
        $queryBuilder->select('s.statusId')
            ->andWhere('s.identifier = :oauth')
            ->andWhere(
                $queryBuilder->expr()->in(
                    's.statusId',
                    $subqueryBuilder->getDql()
                )
            );

        $queryBuilder->setParameter('oauth', $options['oauth']);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @return bool
     */
    protected function isApiRateLimitReached()
    {
        $rateLimitStatus = $this->feedReader->fetchRateLimitStatus();
        $leastUpperBound = ($rateLimitStatus->resources->statuses->{'/statuses/user_timeline'}->limit / 10);

        return $rateLimitStatus->resources->statuses->{'/statuses/user_timeline'}->remaining <= $leastUpperBound;
    }

    /**
     * @param $options
     */
    protected function persistStatuses($options)
    {
        $statuses = $this->feedReader->fetchTimelineStatuses($options);

        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository
         */
        $userStreamRepository = $this->getContainer()->get('weaving_the_web_api.repository.user_stream');
        $userStreamRepository->saveStatuses($statuses, $options['oauth']);
    }
}
