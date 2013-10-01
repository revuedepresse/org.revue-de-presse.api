<?php

namespace WeavingTheWeb\Bundle\AMQPBundle\Command\Twitter;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProduceUserFriendListCommand
 * @package WeavingTheWeb\Bundle\AMQPBundle\Command\Twitter
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProduceUserFriendListCommand extends ContainerAwareCommand
{
    /**
     * @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Reader\FeedReader $feedReader
     */
    protected $feedReader;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    public function configure()
    {
        $this->setName('wtw:amqp:twitter:produce:user_timeline')
            ->setDescription('Produce a message to get a user timeline')
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
            'log',
            null,
            InputOption::VALUE_NONE,
            'The screen name of a user'
        )->setAliases(array('wtw:amqp:tw:prd:utl'));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var \OldSound\RabbitMqBundle\RabbitMq\Producer $producer
         */
        $producer = $this->getContainer()->get('old_sound_rabbit_mq.weaving_the_web_amqp.twitter.user_timeline_producer');
        $this->logger = $this->getContainer()->get('logger');
        $tokens = $this->getTokens($input);

        $this->setupFeedReader($tokens);
        $friends = $this->feedReader->showUserFriends($input->getOption('screen_name'));

        $messageBody = $tokens;

        foreach ($friends->ids as $friend) {
            $user = $this->feedReader->showUser($friend);
            $messageBody['screen_name'] = $user->screen_name;
            $producer->publish(serialize(json_encode($messageBody)));
            $producer->setContentType('application/json');
            $this->logger->info('[publishing new message produced for "' . $user->screen_name . '"]');
        }

        /**
         * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator
         */
        $translator = $this->getContainer()->get('translator');
        $output->writeln($translator->trans('amqp.friendlist.production.success', ['{{ count }}' => count($friends->ids)]));
    }

    /**
     * @param $oauthTokens
     */
    protected function setupFeedReader($oauthTokens)
    {
        /**
         * @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Reader\FeedReader $feedReader
         */
        $this->feedReader = $this->getContainer()->get('weaving_the_web_legacy_provider.feed_reader');
        $this->feedReader->setUserToken($oauthTokens['token']);
        $this->feedReader->setUserSecret($oauthTokens['secret']);
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function getTokens(InputInterface $input)
    {
        if ($input->hasOption('secret') && !is_null($input->getOption('secret'))) {
            $secret = $input->getOption('secret');
        } else {
            $secret = $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_secret.default');
        }
        if ($input->hasOption('token') && !is_null($input->getOption('token'))) {
            $token = $input->getOption('token');
        } else {
            $token = $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_token.default');
        }

        return [
            'secret' => $secret,
            'token' => $token,
        ];
    }
} 