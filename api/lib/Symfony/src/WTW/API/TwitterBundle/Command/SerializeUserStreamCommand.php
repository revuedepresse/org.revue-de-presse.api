<?php

namespace WTW\API\TwitterBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * User Stream serialization command
 *
 * @author Thierry Marianne <thierrym@theodo.fr>
 */
class SerializeUserStreamCommand extends ContainerAwareCommand
{
    /**
     * Configures executable commands
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('wtw:api:twitter:user_stream:serialize')
            ->setDescription('Serialize response returned when accessing user stream endpoint from twitter api')
            ->addOption(
            'token',
            null,
            InputOption::VALUE_REQUIRED,
            'A token is required'
        )
            ->addOption(
            'secret',
            null,
            InputOption::VALUE_REQUIRED,
            'A secret is required'
        )
            ->setAliases(array('wtw:api:tw:usr_str'));
    }

    /**
     * Serializes user stream
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|null|void
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $this->feedReader = $container->get('wtw.provider.feed_reader');
        $this->setCredentials($input);
        $this->feedReader->setOutputInterface($output);
        $this->feedReader->getUserStream();
    }

    /**
     * Sets credentials
     *
     * @param $input
     *
     * @throws \InvalidArgumentException
     */
    protected function setCredentials($input)
    {
        $token  = $input->getOption('token');
        $secret = $input->getOption('secret');

        if (strlen($token) === 0 || strlen($secret) === 0) {
            throw new \InvalidArgumentException('Valid token and secret are required');
        } else {
            $this->feedReader->setUserToken($token);
            $this->feedReader->setUserSecret($secret);
        }
    }
}
