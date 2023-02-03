<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Subscription\Console;

use App\Twitter\Domain\Curation\Repository\PaginatedBatchCollectedEventRepositoryInterface;
use App\Twitter\Domain\Http\Client\CursorAwareHttpClientInterface;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use App\Twitter\Infrastructure\DependencyInjection\MissingDependency;
use stdClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListMemberSubscribeesCommand extends AbstractCommand
{
    private const ARGUMENT_SCREEN_NAME = 'screen_name';

    public CursorAwareHttpClientInterface $cursorAwareHttpClient;

    private PaginatedBatchCollectedEventRepositoryInterface $repository;

    public function setCursorAwareHttpClient(CursorAwareHttpClientInterface $cursorAwareHttpClient): void
    {
        $this->cursorAwareHttpClient = $cursorAwareHttpClient;
    }

    public function setRepository(PaginatedBatchCollectedEventRepositoryInterface $repository): void
    {
        $this->repository = $repository;
    }

    protected function configure()
    {
        $this->setName('app:list-member-subscribees')
            ->setDescription('List the subscribees of a member')
            ->addArgument(
                self::ARGUMENT_SCREEN_NAME,
                InputArgument::REQUIRED,
                'The screen name of a member'
            )->setAliases(['pr:lmsubb']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->guardAgainstMissingDependency();

        $screenName = $input->getArgument(self::ARGUMENT_SCREEN_NAME);
        $friendsList = $this->repository->aggregatedLists(
            $this->cursorAwareHttpClient,
            $screenName
        )->getList();

        array_walk(
            $friendsList,
            function (stdClass $friend) use ($output) {
                $friendAsArray = (array) $friend;
                $output->writeln($this->format($friendAsArray));
                $output->writeln('');
            }
        );

        return self::SUCCESS;
    }

    /**
     * @throws MissingDependency
     */
    private function guardAgainstMissingDependency(): void
    {
        if (!($this->cursorAwareHttpClient instanceof CursorAwareHttpClientInterface)) {
            throw new MissingDependency(
                sprintf(
                    'Dependency of type "%s" is missing',
                    CursorAwareHttpClientInterface::class
                )
            );
        }

        if (!($this->repository instanceof PaginatedBatchCollectedEventRepositoryInterface)) {
            throw new MissingDependency(
                sprintf(
                    'Dependency of type "%s" is missing',
                    PaginatedBatchCollectedEventRepositoryInterface::class
                )
            );
        }
    }

    private function format(array $friendAsArray): string
    {
        return implode(
            PHP_EOL,
            [
                "Name: {$friendAsArray['name']}",
                "Description: {$friendAsArray['description']}",
                "URL: https://twitter/{$friendAsArray['screen_name']}",
                "Followers: {$friendAsArray['followers_count']}",
                "Friends: {$friendAsArray['friends_count']}",
                "Location: {$friendAsArray['location']}",
            ]
        );
    }
}