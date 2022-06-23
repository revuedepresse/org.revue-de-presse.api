<?php

namespace App\FrenchMediaShareholders\Console;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ImportFrenchMediaShareholdersCommand extends Command
{
    public LoggerInterface $logger;

    public ServiceEntityRepository $mediaRepository;

    public ServiceEntityRepository $ownerRepository;

    public string $mediasDirectory;

    public function configure()
    {
        $this->setName('app:import-french-media-shareholders');
    }

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

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getTsvContents(SplFileInfo $file): array
    {
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
