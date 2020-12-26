<?php

declare (strict_types=1);

namespace App\Twitter\Infrastructure\Operation\Console;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Api\Repository\TokenTypeRepositoryInterface;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Domain\Resource\MemberIdentity;
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
    public const ARGUMENT_API_ACCESS_TOKEN = 'api-access-token';

    private TokenTypeRepositoryInterface $tokenTypeRepository;

    private TokenRepositoryInterface $tokenRepository;

    private MemberRepositoryInterface $memberRepository;

    public function __construct(
        $name,
        TokenTypeRepositoryInterface $tokenTypeRepository,
        TokenRepositoryInterface $tokenRepository,
        MemberRepositoryInterface $memberRepository
    ) {
        $this->tokenTypeRepository = $tokenTypeRepository;
        $this->tokenRepository = $tokenRepository;
        $this->memberRepository = $memberRepository;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Load fixtures required for accessing Twitter API into the database')
            ->addArgument(self::ARGUMENT_USER_TOKEN, InputArgument::REQUIRED, 'Twitter API user token')
            ->addArgument(self::ARGUMENT_USER_SECRET, InputArgument::REQUIRED, 'Twitter API user secret')
            ->addArgument(self::ARGUMENT_CONSUMER_KEY, InputArgument::REQUIRED, 'Twitter API consumer key')
            ->addArgument(self::ARGUMENT_CONSUMER_SECRET, InputArgument::REQUIRED, 'Twitter API consumer secret')
            ->addArgument(self::ARGUMENT_API_ACCESS_TOKEN, InputArgument::REQUIRED, 'API access token');
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

        $existingMember = $this->memberRepository->findOneBy(['apiKey' => self::ARGUMENT_API_ACCESS_TOKEN]);
        if (!($existingMember instanceof MemberInterface)) {
            $this->memberRepository->saveApiConsumer(
                new MemberIdentity('api-consumer', '-1'),
                $input->getArgument(self::ARGUMENT_API_ACCESS_TOKEN)
            );
        }

        $output->writeln('<info>Production fixtures have been loaded successfully.</info>');

        return self::RETURN_STATUS_SUCCESS;
    }
}