<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputOption;

/**
 * Class SerializeStatusesCommand
 * @package WeavingTheWeb\Bundle\TwitterBundle\Command
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SerializeStatusesCommand extends ContainerAwareCommand
{
    /**
     * @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Reader\FeedReader $feedReader
     */
    protected $feedReader;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository
     */
    protected $userStreamRepository;

    /**
     * @var bool
     */
    protected $log;

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
                InputOption::VALUE_NONE,
                'Try saving all statuses provided rate limits of Twitter API consumption and user statuses count'
            )
            ->addOption(
                'log',
                null,
                InputOption::VALUE_NONE,
                'Logs count of statuses persisted for each loop'
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
        $this->log = $input->getOption('log');
        $oauthTokens = $this->getOauthTokens($input);
        $options = [
            'oauth' => $oauthTokens['token'],
            'count' => $input->getOption('count'),
            'page' => $input->getOption('page'),
            'screen_name' => $input->getOption('screen_name')
        ];
        $this->setUpFeedReader($oauthTokens);
        $this->userStreamRepository = $this->getContainer()->get('weaving_the_web_api.repository.user_stream');

        $context = $this->updateContext($options);

        while ($context['condition']) {
            $saveStatuses = $this->persistStatuses($context['options']);

            if (!$input->hasOption('greedy') || !$input->getOption('greedy') || is_null($saveStatuses)) {
                break;
            }

            $context = $this->updateContext($context['options']);
        }

        /**
         * @var \Symfony\Component\Translation\Translator $translator
         */
        $translator = $this->getContainer()->get('translator');
        $output->writeln($translator->trans('twitter.statuses.persistence.success'));
    }

    /**
     * @param $options
     * @return array
     */
    protected function updateContext($options)
    {
        $apiRateLimitReached = $this->isApiRateLimitReached();
        $remainingStatuses = $this->remainingStatuses($options);
        $status = $this->userStreamRepository->findNextMaxStatus($options['oauth'], $options['screen_name']);

        if ((count($status) === 1) && array_key_exists('statusId', $status)) {
            $options['max_id'] = $status['statusId'] - 1;
            $this->getContainer()->get('logger')->info('[max id] ' . $options['max_id']);
        }

        return [
            'condition' => !$apiRateLimitReached && $remainingStatuses,
            'options' => $options
        ];
    }

    /**
     * @param $options
     * @param $count
     * @return bool
     */
    protected function remainingStatuses($options)
    {
        $count = $this->userStreamRepository->countStatuses($options['oauth']);
        $user = $this->feedReader->showUser($options['screen_name']);

        return $count < $user->statuses_count;
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
        if (count($statuses) > 0) {
            $savedStatuses = $this->userStreamRepository->saveStatuses($statuses, $options['oauth']);

            if ($this->log) {
                $logger = $this->getContainer()->get('logger');
                $logger->info('[' . count($savedStatuses) . ' status(es) saved]');
            }

            return $savedStatuses;
        } else {
            return null;
        }
    }
}
