<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class MapStatusCommand
 * @package WeavingTheWeb\Bundle\DashboardBundle\Command
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class MapStatusCommand extends ContainerAwareCommand
{
    /**
     * @var \Doctrine\Orm\EntityManager $entityManager
     */
    protected $entityManager;

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
        $this->setName('weaving_the_web:dashboard:map_status')
            ->setDescription('Iterates over statuses using a closure')
            ->addOption('mapper', null, InputOption::VALUE_REQUIRED,
                'PHP Script returning a closure to which each perspective is passed as its first argument')
            ->setAliases(['wtw:das:map:sts']);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $mapper = $input->getOption('mapper');
        $this->validateMapper($mapper);

        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $iterableResult = $this->getIterableResult();

        $this->feedReader = $this->getContainer()->get('weaving_the_web_legacy_provider.feed_reader');
        $this->logger = $this->getContainer()->get('logger');

        /**
         * @var $closure \Closure
         */
        $closure = require($mapper);

        foreach ($iterableResult AS $collection) {
            foreach ($collection as $item) {
                $limitReached = $this->feedReader->isApiRateLimitReached();

                if (is_integer($limitReached) || $limitReached) {
                    $this->logger->info('Twitter API limit has been reached. Now waiting for 15 minutes.');
                    sleep(15*60);
                } else {
                    /**
                     * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream $item
                     */
                    $status = $this->feedReader->showStatus($item->getStatusId());
                    $item = $closure($item, $status, $this->logger);
                    $this->entityManager->persist($item);
                    $this->logger->info(sprintf('Persisted status with id %s', $item->getStatusId()));
                    $this->entityManager->flush($item);
                    $this->entityManager->detach($item);
                }
            }
        }

        $successMessage = $this->getContainer()->get('translator')->trans('status_mapping_success');
        $output->writeln($successMessage);
    }

    /**
     * @param $mapper
     * @throws \InvalidArgumentException
     */
    public function validateMapper($mapper)
    {
        if (!file_exists($mapper)) {
            throw new \InvalidArgumentException('The mapper option value is not valid file');
        }
    }

    /**
     * @return array
     */
    protected function getIterableResult()
    {
        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository
         */
        $userStreamRepository = $this->entityManager->getRepository(
            '\WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream'
        );
        $queryBuilder = $userStreamRepository->createQueryBuilder('u');
        $queryBuilder->where('u.updatedAt is null');
        $query = $queryBuilder->getQuery();

        return $query->iterate();
    }
} 