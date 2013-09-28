<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputOption;

/**
 * Statuses serialization command
 *
 * @author Thierry Marianne <thierrym@theodo.fr>
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
                'oauth',
                null,
                InputOption::VALUE_REQUIRED,
                'OAuth token'
            )
            ->addOption(
                'oauth_secret',
                null,
                InputOption::VALUE_REQUIRED,
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
        $container = $this->getContainer();
        /**
         * @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Reader\FeedReader $feedReader
         */
        $feedReader = $container->get('weaving_the_web_legacy_provider.feed_reader');
        $feedReader->setUserToken($input->getOption('oauth'));
        $feedReader->setUserSecret($input->getOption('oauth_secret'));

        $options = [
            'count' => $input->getOption('count'),
            'page' => $input->getOption('page'),
            'screen_name' => $input->getOption('screen_name')
        ];
        $statuses = $feedReader->fetchTimelineStatuses($options);
        /**
         * @var \WeavingTheWeb\Bundle\DataMiningBundle\ORM\QueryFactory $queryFactory
         */
        $queryFactory = $container->get('weaving_the_web_data_mining.query_factory');
        $usersStatuses = $queryFactory->processUsersStatuses($statuses);
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        foreach ($usersStatuses as $userStatus) {
            /**
             * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream $userStream
             */
            $userStream = $queryFactory->makeUserStream($userStatus);
            $userStream->setIdentifier($input->getOption('oauth'));
            $entityManager->persist($userStream);
        }

        $entityManager->flush();

        /**
         * @var \Symfony\Component\Translation\Translator $translator
         */
        $translator = $this->getContainer()->get('translator');
        $output->writeln($translator->trans('twitter.statuses.persistence.success'));
    }
}
