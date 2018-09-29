<?php

namespace App\Conversation\Command ;

use App\Operation\OperationClock;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\TranslatorInterface;

use WeavingTheWeb\Bundle\AmqpBundle\Command\AggregateAwareCommand;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException;


/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProduceConversationMessagesCommand extends AggregateAwareCommand
{
    /**
     * @var \OldSound\RabbitMqBundle\RabbitMq\Producer
     */
    private $producer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var OperationClock
     */
    public $operationClock;

    /**
     * @var Filesystem
     */
    public $filesystem;

    /**
     * @var string
     */
    public $statusDirectory;

    public function configure()
    {
        $this->setName('weaving_the_web:amqp:produce:conversation')
            ->setDescription('Produce an AMQP message to get a conversation')
         ->addOption(
            'screen_name',
            null,
            InputOption::VALUE_REQUIRED,
            'The screen name of a user'
        )
        ->addOption(
            'producer',
            null,
            InputOption::VALUE_OPTIONAL,
            'A producer key',
            'twitter.conversation_status'
        )->setAliases(array('wtw:amqp:prd:cnv'));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|mixed|null
     * @throws SuspendedAccountException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->operationClock->shouldSkipOperation()) {
            return self::RETURN_STATUS_SUCCESS;
        }

        $this->input = $input;
        $this->output = $output;


        $this->setUpDependencies();
        $onBehalfOf = $this->input->getOption('screen_name');

        $statusIdsExist = $this->filesystem->exists($this->statusDirectory.'/status-ids');

        $this->producer->setContentType('application/json');

        if ($statusIdsExist) {
            $finder = new Finder();
            $finder->in($this->statusDirectory)
                ->name('status-ids');

            $counter = (object)['total_conversations' => 0];

            foreach ($finder->getIterator() as $file) {
                $contents = $file->getContents();
                $statusIds = explode(PHP_EOL, $contents);

                array_map(
                    function ($statusId) use ($counter, $onBehalfOf) {
                        $messageBody = [
                            'status_id' => $statusId,
                            'screen_name' => $onBehalfOf
                        ];
                        $this->producer->publish(serialize(json_encode($messageBody)));

                        $counter->total_conversations++;
                    },
                    $statusIds
                );
            }
        }

        $this->sendMessage(
            $this->translator->trans(
                'amqp.production.conversations.success',
                ['{{ count }}' => $counter->total_conversations],
                'messages'
            ),
            'info'
        );
    }

    /**
     * @param $message
     * @param $level
     * @param \Exception $exception
     */
    private function sendMessage($message, $level, \Exception $exception = null)
    {
        $this->output->writeln($message);

        if ($exception instanceof \Exception) {
            $this->logger->critical($exception->getMessage());

            return;
        }

        $this->logger->$level($message);
    }

    private function setProducer(): void
    {
        $producerKey = $this->input->getOption('producer');

        /** @var \OldSound\RabbitMqBundle\RabbitMq\Producer $producer */
        $this->producer = $this->getContainer()->get(sprintf(
            'old_sound_rabbit_mq.weaving_the_web_amqp.%s_producer', $producerKey
        ));
    }

    /**
     * @return array
     */
    protected function setUpTokens()
    {
        return [
            'secret' => $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_secret.default'),
            'token' => $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_token.default'),
        ];
    }

    private function setUpDependencies()
    {
        $this->setProducer();

        $tokens = $this->setUpTokens();

        $this->setUpLogger();
        $this->setupAccessor($tokens);
        $this->setupAggregateRepository();

        $this->translator = $this->getContainer()->get('translator');
    }
}
