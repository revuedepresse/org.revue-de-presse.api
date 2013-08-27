<?php

namespace WeavingTheWeb\Bundle\AMQPBundle\Command\Twitter;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProduceUserTimelineCommand
 * @package WeavingTheWeb\Bundle\AMQPBundle\Command\Twitter
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProduceUserTimelineCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName('wtw:amqp:twitter:produce:user_timeline')
            ->setDescription('Produce a message to get a user timeline')
            ->addOption(
            'token',
            null,
            InputOption::VALUE_REQUIRED,
            'A token is required'
        )->addOption(
            'secret',
            null,
            InputOption::VALUE_REQUIRED,
            'A secret is required'
        )->setAliases(array('wtw:amqp:tw:prd:utl'));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var \OldSound\RabbitMqBundle\RabbitMq\Producer $producer
         */
        $producer = $this->getContainer()->get('old_sound_rabbit_mq.weaving_the_web_amqp.twitter.user_timeline_producer');
        $messageBody = ['token' => $input->getOption('token'), 'secret' => $input->getOption('secret')];
        $producer->publish(serialize(json_encode($messageBody)));
        $producer->setContentType('application/json');
    }
} 