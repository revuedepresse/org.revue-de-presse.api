<?php
declare(strict_types=1);

namespace App\Conversation\Producer;

use App\Twitter\Infrastructure\Amqp\Command\AggregateAwareCommand;
use App\Twitter\Infrastructure\Operation\OperationClock;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProduceMemberMessagesCommand extends AggregateAwareCommand
{
    /**
     * TODO replace message producer with message bus
     */
//    private $producer;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var OperationClock
     */
    public OperationClock $operationClock;

    public function configure()
    {
        $this->setName('weaving_the_web:amqp:produce:member_messages')
        ->setDescription('Produce an AMQP message to get a status from a member')
        ->setAliases(array('wtw:amqp:prd:mbm'));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|mixed|null
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->operationClock->shouldSkipOperation()) {
            return self::RETURN_STATUS_SUCCESS;
        }

//        $this->producer = $this->getContainer()->get(
//            'old_sound_rabbit_mq.weaving_the_web_amqp.producer.member_status_producer'
//        );

        $this->input = $input;
        $this->output = $output;

        $messageBody = [
            'secret' => $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_secret.default'),
            'token' => $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_token.default')
        ];

        $this->setUpDependencies();

//        $this->producer->setContentType('application/json');

        $records = $this->aggregateRepository->selectAggregatesForWhichNoStatusHasBeenCollected();
        $publications = (object)['count' => 0];

        array_walk(
            $records,
            function ($record) use ($publications, $messageBody) {
                try {
                    $memberProperties = (object)[
                        'id' => $record['member_id'],
                        'screen_name' => $record['member_screen_name'],
                    ];

                    // TODO replace with getMessageMember
//                    $member = $this->getMessageUser($memberProperties);

                    if ($member->isProtected()) {
                        $message = sprintf(
                            'Ignoring protected member with screen name "%s"',
                            $memberProperties->screen_name
                        );
                        $this->logger->info($message);

                        return;
                    }

                    if ($member->isSuspended()) {
                        $message = sprintf(
                            'Ignoring suspended member with screen name "%s"',
                            $memberProperties->screen_name
                        );
                        $this->logger->info($message);

                        return;
                    }

                    $messageBody['screen_name'] = $member->getTwitterUsername();
                    $messageBody['aggregate_id'] = $record['aggregate_id'];


//                    $this->producer->setContentType('application/json');
//                    $this->producer->publish(serialize(json_encode($messageBody)));

                    $publications->count++;
                } catch (Exception $exception) {
                    if ($this->shouldBreakPublication($exception)) {
                        throw $exception;
                    } elseif ($this->shouldContinuePublication($exception)) {
                        return;
                    } else {
                        throw $exception;
                    }
                }
            }
        );

        $this->sendMessage(
            $this->translator->trans(
                'amqp.production.member_messages.success',
                ['{{ count }}' => $publications->count],
                'messages'
            ),
            'info'
        );
    }

    /**
     * @param $message
     * @param $level
     * @param Exception $exception
     */
    private function sendMessage($message, $level, Exception $exception = null)
    {
        $this->output->writeln($message);

        if ($exception instanceof Exception) {
            $this->logger->critical($exception->getMessage());

            return;
        }

        $this->logger->$level($message);
    }

    private function setUpDependencies()
    {
        $this->setUpLogger();
        $this->setupAggregateRepository();

        $this->translator = $this->getContainer()->get('translator');
    }
}
