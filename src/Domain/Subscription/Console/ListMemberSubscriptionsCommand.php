<?php
declare (strict_types=1);

namespace App\Domain\Subscription\Console;

use App\Console\AbstractCommand;
use App\Infrastructure\Collection\Repository\MemberFriendsListCollectedEventRepositoryInterface;
use App\Infrastructure\DependencyInjection\MissingDependency;
use App\Infrastructure\Twitter\Api\Accessor\FriendsAccessorInterface;
use stdClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListMemberSubscriptionsCommand extends AbstractCommand
{
    private const ARGUMENT_SCREEN_NAME = 'screen_name';

    public FriendsAccessorInterface $accessor;
    /**
     * @var MemberFriendsListCollectedEventRepositoryInterface
     */
    private MemberFriendsListCollectedEventRepositoryInterface $repository;

    public function setAccessor(FriendsAccessorInterface $accessor): void
    {
        $this->accessor = $accessor;
    }

    public function setRepository(MemberFriendsListCollectedEventRepositoryInterface $repository): void
    {
        $this->repository = $repository;
    }

    public function configure(): void
    {
        $this->setName('press-review:list-member-subscriptions')
            ->setDescription('List the subscriptions of a member')
            ->addArgument(
                self::ARGUMENT_SCREEN_NAME,
                InputArgument::REQUIRED,
                'The screen name of a member'
            )->setAliases(['pr:lm']);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->guardAgainstMissingDependency();

        $screenName = $input->getArgument(self::ARGUMENT_SCREEN_NAME);
        $friendsList = $this->repository->aggregatedMemberFriendsLists(
            $this->accessor,
            $screenName
        )->getFriendsList();

        array_walk(
            $friendsList,
            function (stdClass $friend) use ($output) {
                $friendAsArray = (array) $friend;
                $output->writeln($this->format($friendAsArray));
                $output->writeln('');
            }
        );

        return self::RETURN_STATUS_SUCCESS;
    }

    /**
     * @throws MissingDependency
     */
    private function guardAgainstMissingDependency(): void
    {
        if (!($this->accessor instanceof FriendsAccessorInterface)) {
            throw new MissingDependency(
                sprintf(
                    'Dependency of type "%s" is missing',
                    FriendsAccessorInterface::class
                )
            );
        }

        if (!($this->repository instanceof MemberFriendsListCollectedEventRepositoryInterface)) {
            throw new MissingDependency(
                sprintf(
                    'Dependency of type "%s" is missing',
                    MemberFriendsListCollectedEventRepositoryInterface::class
                )
            );
        }
    }

    private function format(array $friendAsArray): string
    {
        return implode(
            PHP_EOL,
            [
                "Name: ${friendAsArray['name']}",
                "Description: ${friendAsArray['description']}",
                "URL: https://twitter/${friendAsArray['screen_name']}",
                "Followers: ${friendAsArray['followers_count']}",
                "Friends: ${friendAsArray['friends_count']}",
                "Location: ${friendAsArray['location']}",
            ]
        );
    }
}