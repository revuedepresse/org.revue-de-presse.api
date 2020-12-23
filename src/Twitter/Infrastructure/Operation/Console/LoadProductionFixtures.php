<?php

declare (strict_types=1);

namespace App\Twitter\Infrastructure\Operation\Console;

use App\Twitter\Domain\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Api\Repository\TokenTypeRepositoryInterface;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadProductionFixtures extends AbstractCommand
{
    public const ARGUMENT_USER_TOKEN = 'user-token';
    public const ARGUMENT_USER_SECRET = 'user-secret';
    public const ARGUMENT_CONSUMER_KEY = 'consumer-key';
    public const ARGUMENT_CONSUMER_SECRET = 'consumer-secret';

    private TokenTypeRepositoryInterface $tokenTypeRepository;

    private TokenRepositoryInterface $tokenRepository;

    public function __construct(
        $name,
        TokenTypeRepositoryInterface $tokenTypeRepository,
        TokenRepositoryInterface $tokenRepository
    ) {
        $this->tokenTypeRepository = $tokenTypeRepository;
        $this->tokenRepository = $tokenRepository;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Load fixtures required for accessing Twitter API into the database')
            ->addArgument(self::ARGUMENT_USER_TOKEN, InputArgument::REQUIRED, 'Twitter API user token')
            ->addArgument(self::ARGUMENT_USER_SECRET, InputArgument::REQUIRED, 'Twitter API user secret')
            ->addArgument(self::ARGUMENT_CONSUMER_KEY, InputArgument::REQUIRED, 'Twitter API consumer key')
            ->addArgument(self::ARGUMENT_CONSUMER_SECRET, InputArgument::REQUIRED, 'Twitter API consumer secret');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->tokenTypeRepository->ensureTokenTypesExist();
        $this->tokenRepository->ensureTokenExists(
            $input->getArgument(self::ARGUMENT_USER_TOKEN),
            $input->getArgument(self::ARGUMENT_USER_SECRET),
            $input->getArgument(self::ARGUMENT_CONSUMER_KEY),
            $input->getArgument(self::ARGUMENT_CONSUMER_SECRET)
        );

        $output->writeln('<info>Production fixtures have been loaded successfully.</info>');

        return self::RETURN_STATUS_SUCCESS;
    }
}