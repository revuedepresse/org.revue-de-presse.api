<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Security\Authorization\Console;

use App\Twitter\Domain\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Api\Security\Authorization\AuthorizeAccessInterface;
use App\Twitter\Domain\Membership\Exception\MembershipException;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Api\Security\Authorization\InvalidPinCodeException;
use App\Twitter\Infrastructure\Api\Security\Authorization\Verifier;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use App\Twitter\Infrastructure\Console\Exception\InterruptedConsoleCommandException;
use App\Twitter\Domain\Api\Accessor\MemberProfileAccessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class AuthorizeApplicationCommand extends AbstractCommand implements SignalableCommandInterface
{
    public const COMMAND_NAME = 'devobs:authorize-application';

    private LoggerInterface $logger;

    private TokenRepositoryInterface $tokenRepository;

    private AuthorizeAccessInterface $authorizeAccess;

    private MemberProfileAccessorInterface $memberProfileAccessor;

    private MemberRepositoryInterface $memberRepository;

    public function __construct(
        $name,
        TokenRepositoryInterface $tokenRepository,
        AuthorizeAccessInterface $authorizeAccess,
        MemberProfileAccessorInterface $memberProfileAccessor,
        MemberRepositoryInterface $memberRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($name);

        $this->tokenRepository = $tokenRepository;
        $this->authorizeAccess = $authorizeAccess;
        $this->memberProfileAccessor = $memberProfileAccessor;
        $this->memberRepository = $memberRepository;

        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this->setDescription(
                'Authorize Twitter application accesssing Twitter API on behalf of member.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        try {
            $this->runAuthorizationFlow();
        } catch (InterruptedConsoleCommandException $exception) {
            $this->output->writeln('Quitting now. Bye.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->logger->error($exception);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    public function getSubscribedSignals(): array
    {
        // return here any of the constants defined by PCNTL extension
        // https://www.php.net/manual/en/pcntl.constants.php
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal): void
    {
        if ($signal === SIGINT || $signal === SIGTERM) {
            InterruptedConsoleCommandException::throws();
        }
    }

    private function runAuthorizationFlow(): void
    {
        while (true) {
            $requestToken = $this->authorizeAccess->requestToken();

            $this->output->writeln('Please authorize this Twitter application by following the URL:');
            $authorizationUrl = $this->authorizeAccess->authorizationUrl($requestToken);

            // @see https://symfony.com/doc/current/console/coloring.html#displaying-clickable-links
            $this->output->writeln(
                sprintf(
                    '<href=%s>%s</>',
                    $authorizationUrl,
                    $authorizationUrl
                )
            );

            $helper = $this->getHelper('question');

            $question = new Question('Please enter your PIN code delivered by Twitter to authorize this application: '.PHP_EOL);

            $question->setNormalizer(function ($value) {
                if ($value === null || !is_numeric($value)) {
                    InvalidPinCodeException::throws('The PIN code should not be empty and also a numeric value.');
                }

                return (int) $value;
            });

            try {
                $verifier = $helper->ask($this->input, $this->output, $question);
                $accessToken = $this->authorizeAccess->accessToken($requestToken, new Verifier($verifier));

                break;
            } catch (InvalidPinCodeException $e) {
                $this->output->writeln('Invalid PIN code. Please try again or quit this command with CTRL + C'.PHP_EOL);

                $this->logger->info($e->getMessage());
            }
        }

        $token = $this->tokenRepository->saveAccessToken($accessToken);

        try {
            $member = $this->memberProfileAccessor->getMemberByIdentity(
                new MemberIdentity($accessToken->screenName(), $accessToken->userId())
            );
        } catch (MembershipException $exception) {
            $member = $exception->exceptionalMember();
        }

        $this->memberRepository->saveMember($member->addToken($token));

        $this->output->writeln('This Twitter application has been granted access to Twitter API on your behalf.');
    }
}