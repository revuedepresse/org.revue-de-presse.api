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
            'screen_name' => $input->getOption('screen_name')
        ];

        /**
         * @var \WeavingTheWeb\Bundle\TwitterBundle\Serializer\UserStatus $serializer
         */
        $serializer = $this->getContainer()->get('weaving_the_web_twitter.serializer.user_status');
        $greedyMode = !$input->hasOption('greedy') || $input->getOption('greedy');
        $serializer->serialize($options, $input->getOption('log') ? 'info' : null, $greedyMode);

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
}
