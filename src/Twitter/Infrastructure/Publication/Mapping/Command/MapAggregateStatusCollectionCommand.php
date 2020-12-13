<?php

namespace App\Twitter\Infrastructure\Publication\Mapping\Command;

use App\PublishersList\Entity\SearchMatchingStatus;
use App\PublishersList\Repository\SearchMatchingStatusRepository;
use App\Twitter\Infrastructure\Console\CommandReturnCodeAwareInterface;
use App\Twitter\Infrastructure\Publication\Mapping\RefreshStatusMapping;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;

class MapAggregateStatusCollectionCommand extends Command implements CommandReturnCodeAwareInterface
{
    const OPTION_AGGREGATE = 'aggregate-name';

    const OPTION_EARLIEST_DATE = 'earliest-date';

    const OPTION_LATEST_DATE = 'latest-date';

    const OPTION_MAPPING = 'mapping';

    const OPTION_OAUTH_TOKEN = 'oauth-token';

    const OPTION_OAUTH_SECRET = 'oauth-secret';

    const OPTION_IS_SEARCH = 'is-search';

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var StatusRepository
     */
    public $statusRepository;

    /**
     * @var SearchMatchingStatusRepository
     */
    public $searchMatchingStatusRepository;

    /**
     * @var RefreshStatusMapping
     */
    public $refreshStatusMapping;

    /**
     * @var string
     */
    public $oauthToken;

    /**
     * @var string
     */
    public $oauthSecret;

    /**
     * @var string
     */
    public $timeAfterWhichOperationIsSkipped;

    /**
     * @var string
     */
    public $timeBeforeWhichOperationIsSkipped;

    /**
     * @var \DateTime
     */
    private $earliestDate;

    /**
     * @var \DateTime
     */
    private $latestDate;

    public function configure()
    {
        $this->setName('press-review:map-aggregate-status-collection')
            ->setDescription('Map a service to a collection of aggregate statuses.')
            ->addOption(
                self::OPTION_MAPPING,
                null,
                InputOption::VALUE_REQUIRED,
                'A service name',
                'mapping.refresh_status'
            )->addOption(
                self::OPTION_AGGREGATE,
                null,
                InputOption::VALUE_REQUIRED,
                'A list name'
            )->addOption(
                self::OPTION_IS_SEARCH,
                null,
                InputOption::VALUE_NONE,
                'Should find the statuses resulting from a search'
            )
            ->addOption(
                self::OPTION_EARLIEST_DATE,
                null,
                InputOption::VALUE_OPTIONAL,
                'The earliest date'
            )->addOption(
                self::OPTION_LATEST_DATE,
                null,
                InputOption::VALUE_OPTIONAL,
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $tokens = $this->getTokensFromInput();
        $this->refreshStatusMapping->setOAuthTokens($tokens);

        try {
            $this->guardAgainstInconsistentDates();
            $setDatesFromOptions = $this->setDatesFromOptions();
        } catch (\Exception $exception) {
            $this->output->writeln(sprintf('<error>%s<error>', $exception->getMessage()));
            $this->logger->error($exception->getMessage());
            return self::RETURN_STATUS_FAILURE;
        }

        if (!$setDatesFromOptions) {
            $this->setDatesFromConfiguration();
        }

        $statusCollection = $this->selectStatusCollection();

        $mappedStatuses = $this->statusRepository->mapStatusCollectionToService(
            $this->refreshStatusMapping,
            $statusCollection
        );

        $this->output->writeln($this->getSuccessMessage($mappedStatuses));

        return self::RETURN_STATUS_SUCCESS;
    }

    /**
     * @return array
     */
    protected function getTokensFromInput()
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
            '%d statuses of "%s" aggregate between %s and %s have been mapped to "%s".',
            $statuses->count(),
            $this->input->getOption(self::OPTION_AGGREGATE),
            $this->earliestDate->format('Y-m-d H:i'),
            $this->latestDate->format('Y-m-d H:i'),
            $this->input->getOption(self::OPTION_MAPPING)
        );
    }

    private function guardAgainstInconsistentDates(): void
    {
        if ($this->bothDatesHaveBeenPassed() || $this->noDateOptionHasBeenPassed()) {
            return;
        }

        throw new \LogicException('Both earliest date and latest date should be declared or not at all');
    }

    /**
     * @return bool
     */
    private function bothDatesHaveBeenPassed(): bool
    {
        return $this->input->hasOption(self::OPTION_EARLIEST_DATE) &&
            $this->input->hasOption(self::OPTION_LATEST_DATE);
    }

    /**
     * @return bool
     */
    private function noDateOptionHasBeenPassed(): bool
    {
        return !$this->input->hasOption(self::OPTION_EARLIEST_DATE) &&
            !$this->input->hasOption(self::OPTION_LATEST_DATE);
    }

    /**
     * @return bool
     */
    private function setDatesFromOptions()
    {
        $timezone = new \DateTimeZone('UTC');

        if ($this->input->hasOption(self::OPTION_EARLIEST_DATE) &&
            !empty($this->input->getOption(self::OPTION_EARLIEST_DATE))
        ) {
            $this->earliestDate = new \DateTime(
                $this->input->getOption(self::OPTION_EARLIEST_DATE),
                $timezone
            );
        }

        if ($this->input->hasOption(self::OPTION_LATEST_DATE) &&
            !empty($this->input->getOption(self::OPTION_LATEST_DATE))
        ) {
            $this->latestDate = new \DateTime(
                $this->input->getOption(self::OPTION_LATEST_DATE),
                $timezone
            );

            return true;
        }

        return false;
    }

    private function setDatesFromConfiguration(): void
    {
        $timezone = new \DateTimeZone('UTC');

        $today = new \DateTime('now', $timezone);

        $yesterday = (clone $today)->modify('-1 day');
        $startDate = (new \DateTime(
            $yesterday->format('Y-m-d ' . $this->timeBeforeWhichOperationIsSkipped),
            $timezone
        ))->modify('-1 min');

        $endDate = (new \DateTime(
            $today->format('Y-m-d ' . $this->timeAfterWhichOperationIsSkipped),
            $timezone
        ))->modify('+1 min');

        $this->earliestDate = $startDate;
        $this->latestDate = $endDate;
    }

    /**
     * @return ArrayCollection
     */
    private function selectStatusCollection(): ArrayCollection
    {
        if ($this->input->getOption(self::OPTION_IS_SEARCH)) {
            return $this->searchMatchingStatusRepository->selectSearchMatchingStatusCollection(
                $this->input->getOption(self::OPTION_AGGREGATE)
            )->map(function (SearchMatchingStatus $searchMatchingStatus) {
                return $searchMatchingStatus->status;
            });
        }

        return $this->statusRepository->selectAggregateStatusCollection(
            $this->input->getOption(self::OPTION_AGGREGATE),
            $this->earliestDate,
            $this->latestDate
        );
    }
}
