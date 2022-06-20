<?php
declare (strict_types=1);

namespace App\NewsReview\Infrastructure\Routing\Console;

use App\AbstractCommand;
use App\NewsReview\Domain\Exception\PublishersListRouteAlreadyExposedException;
use App\NewsReview\Domain\Routing\Repository\PublishersListRouteRepositoryInterface;
use App\NewsReview\Domain\Exception\UnknownPublishersListException;
use App\NewsReview\Domain\Repository\PublishersListRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExposePublishersListCommand extends AbstractCommand
{
    public const COMMAND_NAME = 'app:expose-publishers-list';
    public const ARGUMENT_HOSTNAME = 'hostname';
    public const ARGUMENT_PUBLISHERS_LIST_NAME = 'publishers_list_name';

    private PublishersListRepositoryInterface $publishersListRepository;
    private PublishersListRouteRepositoryInterface $publishersListRouteRepository;
    private LoggerInterface $logger;

    public function __construct(
        string $name,
        PublishersListRepositoryInterface $publishersListRepository,
        PublishersListRouteRepositoryInterface $publishersListRouteRepository,
        LoggerInterface $logger
    ) {
        $this->publishersListRouteRepository = $publishersListRouteRepository;
        $this->publishersListRepository = $publishersListRepository;
        $this->logger = $logger;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Expose a publishers list from a public domain')
            ->addArgument(
                self::ARGUMENT_HOSTNAME,
                InputArgument::REQUIRED,
                'hostname of domain where publishers list will be exposed',
            )
            ->addArgument(
                self::ARGUMENT_PUBLISHERS_LIST_NAME,
                InputArgument::REQUIRED,
                'name of a publishers list',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $hostname = $this->input->getArgument(self::ARGUMENT_HOSTNAME);
        $publishersListName = $this->input->getArgument(self::ARGUMENT_PUBLISHERS_LIST_NAME);

        try {
            $publishersList = $this->publishersListRepository->findByName($publishersListName);

            $route = $this->publishersListRouteRepository->exposePublishersList(
                $publishersList,
                $hostname
            );

            $this->output->writeln(
                sprintf(
                    'Publishers list having name "%s" has been successfully exposed at "%s"',
                    $publishersList->name(),
                    $route->hostname()
                )
            );

            return self::SUCCESS;
        } catch (PublishersListRouteAlreadyExposedException $exception) {
            $this->logger->info($exception->getMessage(), ['exception' => $exception]);
            $this->output->writeln($exception->getMessage());

            return self::FAILURE;
        } catch (UnknownPublishersListException $exception) {
            $this->logger->info($exception->getMessage(), ['exception' => $exception]);
            $this->output->writeln(
                sprintf(
                    'No publishers list having name "%s" has been found.',
                    $publishersListName
                )
            );

            return self::FAILURE;
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

            $this->output->writeln(
                sprintf(
                    'Sorry, something went wrong (%s)',
                    $exception->getMessage()
                )
            );

            return self::FAILURE;
        }
    }
}