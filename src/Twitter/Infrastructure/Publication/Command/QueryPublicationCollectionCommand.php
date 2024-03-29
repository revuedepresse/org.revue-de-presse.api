<?php

namespace App\Twitter\Infrastructure\Publication\Command;

use App\Twitter\Domain\Publication\Repository\TweetRepositoryInterface;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueryPublicationCollectionCommand extends Command
{
    const OPTION_SCREEN_NAME = 'screen-name';

    const OPTION_EARLIEST_DATE = 'earliest-date';

    const OPTION_LATEST_DATE = 'latest-date';

    private InputInterface $input;

    public TweetRepositoryInterface $tweetRepository;

    public function configure()
    {
        $this->setName('app:query-publication-collection')
            ->setDescription('Query a collection of publication from criteria.')
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

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $output1 = $output;

        $statusCollection = $this->tweetRepository->queryPublicationCollection(
            $this->input->getOption(self::OPTION_SCREEN_NAME),
            new DateTime($this->input->getOption(self::OPTION_EARLIEST_DATE)),
            new DateTime($this->input->getOption(self::OPTION_LATEST_DATE))
        );

        $output1->writeln($this->getSuccessMessage($statusCollection));

        return self::SUCCESS;
    }

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
