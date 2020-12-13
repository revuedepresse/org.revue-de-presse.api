<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Console;

use App\Twitter\Domain\Publication\Repository\PublicationRepositoryInterface;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * @package App\Twitter\Command
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class MigrateStatusToPublicationsCommand extends Command implements CommandReturnCodeAwareInterface
{
    private PublicationRepositoryInterface $publicationRepository;

    public function setPublicationRepository(PublicationRepositoryInterface $publicationRepository): void
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
        $this->publicationRepository->migrateStatusesToPublications();

        return self::RETURN_STATUS_SUCCESS;
    }
}
