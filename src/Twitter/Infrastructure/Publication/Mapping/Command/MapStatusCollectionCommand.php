<?php


namespace App\Twitter\Infrastructure\Publication\Mapping\Command;


use App\Twitter\Infrastructure\Console\CommandReturnCodeAwareInterface;
use App\Twitter\Infrastructure\Publication\Mapping\RefreshStatusMapping;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;

class MapStatusCollectionCommand extends Command implements CommandReturnCodeAwareInterface
{
    const OPTION_SCREEN_NAME = 'screen-name';

    const OPTION_EARLIEST_DATE = 'earliest-date';

    const OPTION_LATEST_DATE = 'latest-date';

    const OPTION_MAPPING = 'mapping';

    const OPTION_OAUTH_TOKEN = 'oauth-token';

    const OPTION_OAUTH_SECRET = 'oauth-secret';

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

    public function configure()
    {
        $this->setName('press-review:map-status-collection')
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

        $statusCollection = $this->statusRepository->selectStatusCollection(
            $this->input->getOption(self::OPTION_SCREEN_NAME),
            new \DateTime($this->input->getOption(self::OPTION_EARLIEST_DATE)),
            new \DateTime($this->input->getOption(self::OPTION_LATEST_DATE))
        );

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
            '%d statuses of "%s" member between %s and %s have been mapped to "%s".',
            $statuses->count(),
            $this->input->getOption(self::OPTION_SCREEN_NAME),
            $this->input->getOption(self::OPTION_EARLIEST_DATE),
            $this->input->getOption(self::OPTION_LATEST_DATE),
            $this->input->getOption(self::OPTION_MAPPING)
        );
    }
}
