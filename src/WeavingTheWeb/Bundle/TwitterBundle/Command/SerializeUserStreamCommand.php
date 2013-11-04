<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputOption;

/**
 * User Stream serialization command
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SerializeUserStreamCommand extends ContainerAwareCommand
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
        $this->setName('weaving_the_web:twitter:user_stream')
            ->setDescription('Serialize response returned when accessing user stream endpoint from twitter api')
            ->addOption(
            'token',
            null,
            InputOption::VALUE_REQUIRED,
            'A token is required'
        )->addOption(
            'secret',
            null,
            InputOption::VALUE_REQUIRED,
            'A secret is required'
        )->setAliases(array('wtw:tw:str'));
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

        $this->feedReader = $container->get('weaving_the_web_legacy_provider.feed_reader');
        $this->setCredentials($input);
        $this->feedReader->setOutputInterface($output);
        $this->feedReader->getUserStream();
    }

    /**
     * Sets credentials
     *
     * @throws \InvalidArgumentException
     * @param InputInterface $input
     */
    protected function setCredentials(InputInterface $input)
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
