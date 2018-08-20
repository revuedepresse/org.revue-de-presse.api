<?php

namespace App\StatusCollection\Command;

use App\Console\CommandReturnCodeAwareInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;

class SelectStatusCollectionCommand extends Command implements CommandReturnCodeAwareInterface
{
    const OPTION_SCREEN_NAME = 'screen-name';

    const OPTION_EARLIEST_DATE = 'earliest-date';

    const OPTION_LATEST_DATE = 'latest-date';

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var StatusRepository
     */
    public $statusRepository;

    public function configure()
    {
        $this->setName('press-review:select-status-collection')
            ->setDescription('Select a collection of status from criteria.')
            ->addOption(
                self::OPTION_SCREEN_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'A member screen name'
            )->addOption(
                self::OPTION_EARLIEST_DATE,
                null,
                InputOption::VALUE_REQUIRED,
                'The earliest date'
            )->addOption(
                self::OPTION_LATEST_DATE,
                null,
                InputOption::VALUE_REQUIRED,
                'The latest date'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $statusCollection = $this->statusRepository->selectStatusCollection(
            $this->input->getOption(self::OPTION_SCREEN_NAME),
            new \DateTime($this->input->getOption(self::OPTION_EARLIEST_DATE)),
            new \DateTime($this->input->getOption(self::OPTION_LATEST_DATE))
        );

        $this->output->writeln($this->getSuccessMessage($statusCollection));

        return self::RETURN_STATUS_SUCCESS;
    }

    /**
     * @param ArrayCollection $statusCollection
     * @return string
     */
    private function getSuccessMessage(ArrayCollection $statusCollection): string
    {
        return sprintf(
            '%d statuses of "%s" member between %s and %s have been found.',
            $statusCollection->count(),
            $this->input->getOption(self::OPTION_SCREEN_NAME),
            $this->input->getOption(self::OPTION_EARLIEST_DATE),
            $this->input->getOption(self::OPTION_LATEST_DATE)
        );
    }
}
