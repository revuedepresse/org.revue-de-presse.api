<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Subscription\Console;

use App\Twitter\Domain\Curation\Exception\ListsBatchNotFoundException;
use App\Twitter\Domain\Curation\Repository\ListsBatchCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use App\Twitter\Infrastructure\Curation\Repository\ListsBatchCollectedEventRepository;
use App\Twitter\Infrastructure\Http\Resource\PublishersList;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListSubscriptionsToPublishersListsCommand extends AbstractCommand
{
    public const COMMAND_NAME = 'app:list-subscriptions-to-publishers-lists';
    public const ARGUMENT_SCREEN_NAME = 'screen_name';
    public const OPTION_PUBLISHERS_LIST = 'publishers_list';

    private LoggerInterface $logger;

    private ListsBatchCollectedEventRepository $repository;

    public function __construct(
        $name,
        ListsBatchCollectedEventRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('List subscriptions of a Twitter member via Twitter lists')
            ->addArgument(
                self::ARGUMENT_SCREEN_NAME,
                InputArgument::REQUIRED,
                'a Twitter member screen name'
            )
            ->addOption(
                self::OPTION_PUBLISHERS_LIST,
                null,
                InputOption::VALUE_OPTIONAL,
                'a list name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $screenName = $this->input->getArgument(self::ARGUMENT_SCREEN_NAME);

        try {
            $subscriptions = $this->repository->byScreenName($screenName);
        } catch (ListsBatchNotFoundException $e) {
            $this->output->writeln(
                sprintf(
                    'No subscription to a Twitter list can be found for Twitter member having "%s" as screen name.',
                    $screenName
                )
            );
            $this->logger->info($e->getMessage(), [
                'exception' => $e,
                'screen_name' => $screenName,
            ]);

            return self::FAILURE;
        }

        $rows = [];
        $subscriptions->map(
            function (PublishersList $list) use  (&$rows) {
                $rows[] = [$list->name(), $list->id()];

                return $list;
            }
        );

        $table = new Table($this->output);
        $table
            ->setHeaders(['Name', 'Id'])
            ->setRows($rows)
        ;

        $table->render();

        return self::SUCCESS;
    }
}