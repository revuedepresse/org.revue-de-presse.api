<?php

declare (strict_types=1);

namespace App\Twitter\Infrastructure\Operation\Console;

use App\Twitter\Domain\Api\Repository\TokenTypeRepositoryInterface;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadProductionFixtures extends AbstractCommand
{
    private TokenTypeRepositoryInterface $tokenTypeRepository;

    public function __construct(
        $name,
        TokenTypeRepositoryInterface $tokenTypeRepository
    ) {
        $this->tokenTypeRepository = $tokenTypeRepository;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->tokenTypeRepository->ensureTokenTypesExist();

        $output->writeln('<info>Production fixtures have been loaded successfully.</info>');

        return self::RETURN_STATUS_SUCCESS;
    }
}