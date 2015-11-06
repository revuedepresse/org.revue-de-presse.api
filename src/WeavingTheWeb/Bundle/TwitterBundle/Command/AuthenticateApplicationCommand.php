<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AuthenticateApplicationCommand
 * @package WeavingTheWeb\Bundle\TwitterBundle\Command
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AuthenticateApplicationCommand extends ContainerAwareCommand
{
    /**
     * Configures executable commands
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('weaving-the-web:twitter:authenticate')
            ->addOption(
                'key',
                null,
                InputOption::VALUE_OPTIONAL,
                'Consumer key'
            )
            ->addOption(
                'secret',
                null,
                InputOption::VALUE_OPTIONAL,
                'Consumer secret'
            )
            ->setDescription('Authenticates application')
            ->setAliases(array('wtw:tw:auth'));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasOption('key')) {
            $key = $input->getOption('key');
        } else {
            $key = null;

        }
        if ($input->hasOption('secret')) {
            $secret =  $input->getOption('secret');
        } else {
            $secret = null;
        }

        try {
            /**
             * @var $authenticator \WeavingTheWeb\Bundle\TwitterBundle\Security\ApplicationAuthenticator
             */
            $authenticator = $this->getContainer()->get('weaving_the_web_twitter.application_authenticator');
            $authenticationResult = $authenticator->authenticate($key, $secret);
            $key = $authenticationResult['consumer_key'];

            /**
             * @var \Symfony\Component\Translation\Translator $translator
             */
            $translator = $this->getContainer()->get('translator');
            $output->writeln($translator->trans('twitter.success.authentication', ['{{ consumer_key }}' => $key]));
        } catch (\Exception $exception) {
            $this->getContainer()->get('logger')->error($exception->getMessage());

            return 1;
        }

        return 0;
    }

}
