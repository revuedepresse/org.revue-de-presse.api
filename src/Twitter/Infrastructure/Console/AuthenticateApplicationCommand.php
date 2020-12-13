<?php

namespace App\Twitter\Infrastructure\Console;

use App\Membership\Infrastructure\Security\Authentication\Authenticator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AuthenticateApplicationCommand extends Command
{
    /**
     * Configures executable commands
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('weaving_the_web:twitter:authenticate')
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
             * @var Authenticator $authenticator
             */
            $authenticator = $this->getContainer()->get('weaving_the_web_twitter.application_authenticator');
            $authenticationResult = $authenticator->authenticate($key, $secret);
            $key = $authenticationResult['consumer_key'];

            /**
             * @var TranslatorInterface $translator
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