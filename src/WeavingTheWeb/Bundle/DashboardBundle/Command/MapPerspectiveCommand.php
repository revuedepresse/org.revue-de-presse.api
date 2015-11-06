<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * @package WeavingTheWeb\Bundle\DashboardBundle\Command
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class MapPerspectiveCommand extends ContainerAwareCommand
{
    /**
     * @var \Doctrine\Orm\EntityManager $entityManager
     */
    protected $entityManager;

    public function configure()
    {
        $this->setName('weaving_the_web:dashboard:map_perspective')
            ->setDescription('Iterates over perspectives using a closure')
            ->addOption('mapper', null, InputOption::VALUE_REQUIRED,
                'PHP Script returning a closure to which each perspective is passed as its first argument')
            ->setAliases(['wtw:das:map:per']);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $mapper = $input->getOption('mapper');
        $this->validateMapper($mapper);

        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $iterableResult = $this->getIterableResult();

        /**
         * @var $closure \Closure
         */
        $closure = require($mapper);

        foreach ($iterableResult AS $collection) {
            foreach ($collection as $perspective) {
                $perspective = $closure($perspective);
                $this->entityManager->persist($perspective);
                $this->entityManager->flush($perspective);
                $this->entityManager->detach($perspective);
            }
        }

        $successMessage = $this->getContainer()->get('translator')->trans('perspective_mapping_success');
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
         * @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository $perspectiveRepository
         */
        $perspectiveRepository = $this->entityManager->getRepository(
            'WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective'
        );
        $queryBuilder = $perspectiveRepository->createQueryBuilder('p');
        $query = $queryBuilder->getQuery();

        return $query->iterate();
    }
} 
