<?php
declare(strict_types=1);

namespace App\Twitter\Command;

use App\Api\Repository\StatusRepository;
use App\Twitter\Repository\PublicationRepositoryInterface;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * @package App\Twitter\Command
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class MigrateStatusToPublications extends Command
{
    private StatusRepository $statusRepository;

    public function setStatusRepository(StatusRepository $statusRepository): void
    {
        $this->statusRepository = $statusRepository;
    }

    private PublicationRepositoryInterface $publicationRepository;

    public function setPublicationRepositoryRepository(PublicationRepositoryInterface $publicationRepository): void
    {
        $this->publicationRepository = $publicationRepository;
    }

    /**
     * Configures executable commands
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('press-review:migrate-status-to-publications')
            ->setDescription('Migrate status to publications');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $statuses = $this->statusRepository->findBy([
            'indexed' => 0
        ], [], 10000);

    }
}
