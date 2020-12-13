<?php

namespace App\PressMedia\Command;

use App\Twitter\Infrastructure\Console\CommandReturnCodeAwareInterface;
use App\PressMedia\Repository\MediaRepository;
use App\PressMedia\Repository\OwnerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ImportMediaRelationshipsCommand extends Command implements CommandReturnCodeAwareInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var MediaRepository
     */
    public $mediaRepository;

    /**
     * @var OwnerRepository
     */
    public $ownerRepository;

    /**
     * @var string
     */
    public $mediasDirectory;

    public function configure()
    {
        $this->setName('import-media-relationships');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $finder = new Finder();
            $iterator = $finder->files()
                ->in($this->mediasDirectory)
                ->getIterator();
            $files = array_values(iterator_to_array($iterator));


            $mediaFileContents = $this->getTsvContents($files[1]);
            $mediasCollection = $this->mediaRepository->saveMediasFromProperties($mediaFileContents);

            $mediaRelationshipProperties = $this->getTsvContents($files[0]);
            $this->ownerRepository->saveOwnersFromProperties($mediaRelationshipProperties, $mediasCollection);

            $output->writeln('The media have been successfully imported');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $output->writeln($exception->getMessage());

            return self::RETURN_STATUS_FAILURE;
        }

        return self::RETURN_STATUS_SUCCESS;
    }

    /**
     * @param SplFileInfo $file
     * @return array
     * @throws \Exception
     */
    private function getTsvContents(SplFileInfo $file): array
    {
        /** @var SplFileInfo $file */
        $lines = explode("\n", trim($file->getContents()));
        $fields = array_map(
            function ($line) {
                return str_getcsv(
                    str_replace(
                        "\t",
                        ';',
                        $line
                    ),
                    ';'
                );
            },
            $lines
        );

        // Remove header
        return array_slice($fields, 1);
    }
}
