<?php

namespace App\Twitter\Infrastructure\Security\Console;

use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
use function Safe\gzdecode as safeGzipDecode;

class RequestTwitterApiAccessTokenCommand extends AbstractCommand
{

    const COMMAND_NAME = 'app:request-access-token';

    public function __construct(private readonly HttpClientInterface $client)
    {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName(self::COMMAND_NAME);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $tweetsCollection = $this->client->contactEndpoint(
            sprintf(
                '%s.json?tweet_mode=extended&include_entities=1&include_rts=1&exclude_replies=0&trim_user=0&screen_name=%s',
                $this->client::API_ENDPOINT_MEMBER_TIMELINE,
                'ratoulechat'
            )
        );

        $this->output->writeln(sprintf('%d tweets have been fetched.', count($tweetsCollection)));

        return 0;
    }
}