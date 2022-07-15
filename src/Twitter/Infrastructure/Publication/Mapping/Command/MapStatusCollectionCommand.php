<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Mapping\Command;

use App\Twitter\Domain\Publication\Repository\TweetRepositoryInterface;
use App\Twitter\Infrastructure\Publication\Mapping\RefreshStatusMapping;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MapStatusCollectionCommand extends Command
{
    const OPTION_SCREEN_NAME = 'screen-name';

    const OPTION_EARLIEST_DATE = 'earliest-date';

    const OPTION_LATEST_DATE = 'latest-date';

    const OPTION_MAPPING = 'mapping';

    const OPTION_OAUTH_TOKEN = 'oauth-token';

    const OPTION_OAUTH_SECRET = 'oauth-secret';

    private InputInterface $input;

    public TweetRepositoryInterface $tweetRepository;

    public RefreshStatusMapping $refreshStatusMapping;

    public string $oauthToken;

    public string $oauthSecret;

    public function configure()
    {
        $this->setName('app:map-status-collection')
            ->setDescription('Map a service to a collection of statuses.')
            ->addOption(
                self::OPTION_MAPPING,
                null,
                InputOption::VALUE_REQUIRED,
                'A service name'
            )->addOption(
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
            )->addOption(
                self::OPTION_OAUTH_TOKEN,
                null,
                InputOption::VALUE_OPTIONAL,
                'A OAuth token'
            )->addOption(
                self::OPTION_OAUTH_SECRET,
                null,
                InputOption::VALUE_OPTIONAL,
                'A OAuth secret'
            )
        ;
    }

    /**
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $output1 = $output;

        $tokens = $this->getTokensFromInput();
        $this->refreshStatusMapping->setOAuthTokens($tokens);

        $statusCollection = $this->tweetRepository->queryPublicationCollection(
            $this->input->getOption(self::OPTION_SCREEN_NAME),
            new DateTime($this->input->getOption(self::OPTION_EARLIEST_DATE)),
            new DateTime($this->input->getOption(self::OPTION_LATEST_DATE))
        );

        $mappedStatuses = $this->tweetRepository->mapStatusCollectionToService(
            $this->refreshStatusMapping,
            $statusCollection
        );

        $output1->writeln($this->getSuccessMessage($mappedStatuses));

        return self::SUCCESS;
    }

    protected function getTokensFromInput(): array
    {
        $token = $this->oauthToken;
        if ($this->input->hasOption(self::OPTION_OAUTH_TOKEN) &&
            !empty($this->input->getOption(self::OPTION_OAUTH_TOKEN))) {
            $token = $this->input->getOption(self::OPTION_OAUTH_TOKEN);
        }

        $secret = $this->oauthSecret;
        if ($this->input->hasOption(self::OPTION_OAUTH_SECRET) &&
            !empty($this->input->getOption(self::OPTION_OAUTH_SECRET))) {
            $secret = $this->input->getOption(self::OPTION_OAUTH_SECRET);
        }

        return [
            'secret' => $secret,
            'token' => $token,
        ];
    }

    /**
     * @param ArrayCollection $statuses
     * @return string
     */
    private function getSuccessMessage(ArrayCollection $statuses): string
    {
        return sprintf(
            '%d statuses of "%s" member between %s and %s have been mapped to "%s".',
            $statuses->count(),
            $this->input->getOption(self::OPTION_SCREEN_NAME),
            $this->input->getOption(self::OPTION_EARLIEST_DATE),
            $this->input->getOption(self::OPTION_LATEST_DATE),
            $this->input->getOption(self::OPTION_MAPPING)
        );
    }
}
