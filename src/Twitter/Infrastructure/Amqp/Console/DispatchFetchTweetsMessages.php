<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Console;

use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Infrastructure\Amqp\Exception\SkippableOperationException;
use App\Twitter\Infrastructure\Amqp\Exception\UnexpectedOwnershipException;
use App\Twitter\Infrastructure\Http\Entity\Token;
use App\Twitter\Infrastructure\Http\Exception\InvalidSerializedTokenException;
use App\Twitter\Infrastructure\DependencyInjection\OwnershipAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\DispatchAmqpMessagesToFetchTweetsTrait;
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Infrastructure\Exception\OverCapacityException;
use App\Twitter\Infrastructure\InputConverter\InputToCurationRuleset;
use Doctrine\DBAL\Exception as DBALException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DispatchFetchTweetsMessages extends TwitterListAwareCommand
{
    private const ARGUMENT_SCREEN_NAME                  = CurationRulesetInterface::RULE_SCREEN_NAME;

    private const OPTION_BEFORE                         = CurationRulesetInterface::RULE_BEFORE;
    private const OPTION_CURSOR                         = CurationRulesetInterface::RULE_CURSOR;
    private const OPTION_FILTER_BY_TWEET_OWNER_USERNAME = CurationRulesetInterface::RULE_FILTER_BY_TWEET_OWNER_USERNAME;
    private const OPTION_IGNORE_WHISPERS                = CurationRulesetInterface::RULE_IGNORE_WHISPERS;
    private const OPTION_INCLUDE_OWNER                  = CurationRulesetInterface::RULE_INCLUDE_OWNER;
    private const OPTION_LIST                           = CurationRulesetInterface::RULE_LIST;
    private const OPTION_LISTS                          = CurationRulesetInterface::RULE_LISTS;

    private const OPTION_OAUTH_TOKEN                    = 'oauth_token';
    private const OPTION_OAUTH_SECRET                   = 'oauth_secret';

    use OwnershipAccessorTrait;
    use DispatchAmqpMessagesToFetchTweetsTrait;
    use TranslatorTrait;

    private CurationRulesetInterface $ruleset;

    public function configure()
    {
        $this->setName('app:dispatch-messages-to-fetch-member-tweets')
            ->setDescription('Dispatch AMQP messages to fetch member tweets.')
            ->addOption(
                self::OPTION_OAUTH_TOKEN,
                mode: InputOption::VALUE_OPTIONAL,
                description: 'A token is required'
            )->addOption(
                self::OPTION_OAUTH_SECRET,
                null,
                mode: InputOption::VALUE_OPTIONAL,
                description: 'A secret is required'
            )->addOption(
                self::OPTION_LIST,
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Single list to which production is restricted to'
            )
            ->addOption(
                self::OPTION_LISTS,
                mode: InputOption::VALUE_OPTIONAL,
                description: 'List collection to which publication of messages is restricted to'
            )
            ->addOption(
                self::OPTION_CURSOR,
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Cursor from which ownership are to be fetched'
            )->addOption(
                self::OPTION_FILTER_BY_TWEET_OWNER_USERNAME,
                mode: InputOption::VALUE_OPTIONAL,
                description:'Filter by Twitter member username'
            )->addOption(
                self::OPTION_BEFORE,
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Date before which statuses should have been created'
            )->addOption(
                self::OPTION_INCLUDE_OWNER,
                mode: InputOption::VALUE_NONE,
                description: 'Should add owner to the list of accounts to be considered'
            )->addOption(
                self::OPTION_IGNORE_WHISPERS,
                mode: InputOption::VALUE_NONE,
                description: 'Should ignore whispers (publication from members having not published anything for a month)'
            )
            ->addArgument(
                self::ARGUMENT_SCREEN_NAME,
                mode: InputArgument::REQUIRED,
                description: 'A member screen name'
            );
    }

    /**
     * Instance of MemberIdentityProcessorInterface is responsible for dispatching AMQP messages
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;

        try {
            $this->setUpDependencies();
        } catch (SkippableOperationException $exception) {
            $this->output->writeln($exception->getMessage());
        } catch (InvalidSerializedTokenException $exception) {
            $this->logger->info($exception->getMessage());

            return self::FAILURE;
        }

        $returnStatus = self::FAILURE;

        try {
            $this->DispatchAmqpMessagesToFetchTweets->dispatchFetchTweetsMessages(
                InputToCurationRuleset::convertInputToCurationRuleset($input),
                Token::fromArray($this->getTokensFromInputOrFallback()),
                function ($message) {
                    $this->output->writeln($message);
                }
            );

            $returnStatus = self::SUCCESS;
        } catch (DBALException $exception) {
            if ($exception->getPrevious()->getSqlState() === '08006') {
                $this->logger->emergency(
                    <<< 'EMERGENCY'
Is the database service up and running?
Is it possible to query said database with parameters
from the application configuration file (dot env files)?
EMERGENCY,
                    [
                        'message' => $exception->getMessage(),
                        'exception' => $exception->getTrace()
                    ]
                );

                return self::FAILURE;
            }


            $this->logger->emergency(
                $exception->getMessage(),
                ['exception' => $exception->getTrace()]
            );
        } catch (UnexpectedOwnershipException|OverCapacityException $exception) {
            $this->logger->error(
                $exception->getMessage(),
                ['exception' => $exception]
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                ['exception' => $exception]
            );
        }

        return $returnStatus;
    }

    /**
     * @throws InvalidSerializedTokenException
     */
    private function setUpDependencies(): void
    {
        $this->httpClient->fromToken(
            Token::fromArray(
                $this->getTokensFromInputOrFallback()
            )
        );
    }
}
