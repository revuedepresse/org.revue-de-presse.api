<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Command;

use App\Twitter\Domain\Curation\PublicationStrategyInterface;
use App\Twitter\Infrastructure\Amqp\Exception\SkippableOperationException;
use App\Twitter\Infrastructure\Amqp\Exception\UnexpectedOwnershipException;
use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Api\Exception\InvalidSerializedTokenException;
use App\Twitter\Infrastructure\DependencyInjection\OwnershipAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublicationMessageDispatcherTrait;
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Infrastructure\Exception\OverCapacityException;
use App\Twitter\Infrastructure\InputConverter\InputToCollectionStrategy;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package App\Twitter\Infrastructure\Amqp\Command
 */
class FetchPublicationMessageDispatcher extends AggregateAwareCommand
{
    private const ARGUMENT_SCREEN_NAME          = PublicationStrategyInterface::RULE_SCREEN_NAME;

    private const OPTION_LIST                   = PublicationStrategyInterface::RULE_LIST;
    private const OPTION_BEFORE                 = PublicationStrategyInterface::RULE_BEFORE;
    private const OPTION_MEMBER_RESTRICTION     = PublicationStrategyInterface::RULE_MEMBER_RESTRICTION;
    private const OPTION_INCLUDE_OWNER          = PublicationStrategyInterface::RULE_INCLUDE_OWNER;
    private const OPTION_IGNORE_WHISPERS        = PublicationStrategyInterface::RULE_IGNORE_WHISPERS;
    private const OPTION_LISTS                  = PublicationStrategyInterface::RULE_LISTS;
    private const OPTION_CURSOR                 = PublicationStrategyInterface::RULE_CURSOR;

    private const OPTION_OAUTH_TOKEN            = 'oauth_token';
    private const OPTION_OAUTH_SECRET           = 'oauth_secret';

    use OwnershipAccessorTrait;
    use PublicationMessageDispatcherTrait;
    use TranslatorTrait;

    private PublicationStrategyInterface $collectionStrategy;

    public function configure()
    {
        $this->setName('devobs:dispatch-messages-to-fetch-member-statuses')
            ->setDescription('Dispatch AMQP messages to fetch member publications.')
            ->addOption(
                self::OPTION_OAUTH_TOKEN,
                null,
                InputOption::VALUE_OPTIONAL,
                'A token is required'
            )->addOption(
                self::OPTION_OAUTH_SECRET,
                null,
                InputOption::VALUE_OPTIONAL,
                'A secret is required'
            )->addOption(
                self::OPTION_LIST,
                null,
                InputOption::VALUE_OPTIONAL,
                'A list to which production is restricted to'
            )
            ->addOption(
                self::OPTION_LISTS,
                'l',
                InputOption::VALUE_OPTIONAL,
                'List to which publication of messages is restricted to'
            )
            ->addOption(
                self::OPTION_CURSOR,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Cursor from which ownership are to be fetched'
            )->addOption(
                self::OPTION_MEMBER_RESTRICTION,
                'mr',
                InputOption::VALUE_OPTIONAL,
                'Restrict to member, which screen name has been passed as value of this option'
            )->addOption(
                self::OPTION_BEFORE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Date before which statuses should have been created'
            )->addOption(
                self::OPTION_INCLUDE_OWNER,
                null,
                InputOption::VALUE_NONE,
                'Should add owner to the list of accounts to be considered'
            )->addOption(
                self::OPTION_IGNORE_WHISPERS,
                'iw',
                InputOption::VALUE_NONE,
                'Should ignore whispers (publication from members having not published anything for a month)'
            )
            ->addArgument(
                self::ARGUMENT_SCREEN_NAME,
                InputArgument::REQUIRED,
                'A member screen name'
            )
            ->setAliases(['pr:d-m-t-f-m-s']);
    }

    /**
     * \App\Twitter\Infrastructure\Amqp\ResourceProcessor\MemberIdentityProcessor->dispatchPublications
     * method is responsible for dispatching AMQP messages
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

        $this->collectionStrategy = InputToCollectionStrategy::convertInputToCollectionStrategy($input);

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
            $this->publicationMessageDispatcher->dispatchPublicationMessages(
                $this->collectionStrategy,
                Token::fromArray($this->getTokensFromInputOrFallback()),
                function ($message) {
                    $this->output->writeln($message);
                }
            );

            $returnStatus = self::SUCCESS;
        } catch (UnexpectedOwnershipException|OverCapacityException $exception) {
            $this->logger->error(
                $exception->getMessage(),
                ['stacktrace' => $exception->getTraceAsString()]
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                ['stacktrace' => $exception->getTraceAsString()]
            );
        }

        return $returnStatus;
    }

    /**
     * @throws InvalidSerializedTokenException
     */
    private function setUpDependencies(): void
    {
        $this->setUpLogger();

        $this->accessor->fromToken(
            Token::fromArray(
                $this->getTokensFromInputOrFallback()
            )
        );

        $this->setupAggregateRepository();
    }
}
