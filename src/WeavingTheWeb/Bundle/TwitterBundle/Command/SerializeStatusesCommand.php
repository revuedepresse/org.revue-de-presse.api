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

        $this->setName('weaving_the_web:twitter:statuses:serialize')
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
                'count',
                null,
                InputOption::VALUE_REQUIRED,
                'Results count',
                200
            )
            ->addOption(
                'greedy',
                null,
                InputOption::VALUE_NONE,
                'Try saving all statuses provided rate limits of Twitter API consumption and user statuses count'
            )
            ->addOption(
                'bearer',
                null,
                InputOption::VALUE_NONE,
                'Use application bearer token'
            )
            ->setDescription('Serialize response returned when accessing user statuses endpoint from twitter api')
            ->setAliases(array('wtw:tw:sts'));
    }

    /**
     * @param InputInterface $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oauthTokens = $this->getOauthTokens($input);
        $options = [
            'oauth' => $oauthTokens['token'],
            'count' => $input->getOption('count'),
            'screen_name' => $input->getOption('screen_name'),
            'bearer' => $input->getOption('bearer'),
        ];

        /**
         * @var \WeavingTheWeb\Bundle\TwitterBundle\Serializer\UserStatus $serializer
         */
        $serializer = $this->getContainer()->get('weaving_the_web_twitter.serializer.user_status');
        if ($input->hasOption('bearer') && $input->getOption('bearer')) {
            $header = $this->getAuthenticationHeader();
            $serializer->setupAccessor(['authentication_header' => $header]);
        }

        $greedy = $input->hasOption('greedy') && $input->getOption('greedy');
        $success = $serializer->serialize($options, $greedy);

        /**
         * @var \Symfony\Component\Translation\Translator $translator
         */
        $translator = $this->getContainer()->get('translator');
        $output->writeln($translator->trans('twitter.statuses.persistence.success'));

        return $success ? 0 : 1;
    }

    /**
     * @param $oauthTokens
     * @return string
     */
    protected function getAuthenticationHeader()
    {
        /**
         * @var \WeavingTheWeb\Bundle\TwitterBundle\Security\ApplicationAuthenticator $authenticator
         */
        $authenticator = $this->getContainer()->get('weaving_the_web_twitter.application_authenticator');
        $authenticationResult = $authenticator->authenticate(
            $this->getContainer()->getParameter('weaving_the_web_twitter.consumer_key'),
            $this->getContainer()->getParameter('weaving_the_web_twitter.consumer_secret')
        );

        return 'Bearer ' . $authenticationResult['access_token'];
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
