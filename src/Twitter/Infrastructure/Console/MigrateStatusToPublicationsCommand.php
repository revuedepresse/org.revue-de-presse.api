<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Console;

use App\Twitter\Domain\Publication\Repository\TweetPublicationPersistenceLayerInterface;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class MigrateStatusToPublicationsCommand extends Command
{
    private TweetPublicationPersistenceLayerInterface $publicationRepository;

    public function setTweetPublicationPersistenceLayer(TweetPublicationPersistenceLayerInterface $publicationRepository): void
    {
        $this->publicationRepository = $publicationRepository;
    }

    protected function configure()
    {
        parent::configure();

        $this->setName('app:migrate-status-to-publications')
            ->setDescription('Migrate status to publications');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->publicationRepository->migrateStatusesToPublications();

        return self::SUCCESS;
    }
}
