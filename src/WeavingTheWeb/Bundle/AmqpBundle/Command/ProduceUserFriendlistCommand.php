<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException,
    WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException;
use WTW\UserBundle\Entity\User;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProduceUserFriendListCommand extends AccessorAwareCommand
{
    public function configure()
    {
        $this->setName('weaving-the-web:amqp:produce:user-timeline')
            ->setDescription('Produce a message to get a user timeline')
            ->addOption(
            'oauth_token',
            null,
            InputOption::VALUE_OPTIONAL,
            'A token is required'
        )->addOption(
            'oauth_secret',
            null,
            InputOption::VALUE_OPTIONAL,
            'A secret is required'
        )->addOption(
            'screen_name',
            null,
            InputOption::VALUE_REQUIRED,
            'The screen name of a user'
        )->addOption(
            'routing_key',
            null,
            InputOption::VALUE_OPTIONAL,
            'A producer key'
        )
        ->addOption(
            'producer',
            null,
            InputOption::VALUE_OPTIONAL,
            'A producer key',
            'twitter.user_status'
        )->setAliases(array('wtw:amqp:tw:prd:utl'));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $producerKey = $input->getOption('producer');

        /** @var \OldSound\RabbitMqBundle\RabbitMq\Producer $producer */
        $producer = $this->getContainer()->get(sprintf(
            'old_sound_rabbit_mq.weaving_the_web_amqp.%s_producer', $producerKey
        ));

        if ($input->hasOption('routing_key') && !is_null($input->getOption('routing_key'))) {
            $routingKey = $input->getOption('routing_key');
        } else {
            $routingKey = '';
        }

        $tokens = $this->getTokens($input);

        $this->setUpLogger();
        $this->setupAccessor($tokens);
        $friends = $this->accessor->showUserFriends($input->getOption('screen_name'));

        $messageBody = $tokens;

        /** @var \WTW\UserBundle\Repository\UserRepository $userRepository */
        $userRepository = $this->getContainer()->get('wtw_user.repository.user');

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');

        $invalidUsers = 0;

        foreach ($friends->ids as $friend) {
            $result = $userRepository->findOneBy(['twitterID' => $friend]);

            if (count($result) === 1) {
                $user = $result;
            } else {
                try {
                    $twitterUser = $this->accessor->showUser($friend);
                    if (isset($twitterUser->protected) && $twitterUser->protected) {
                        $protectedAccount = $translator->trans(
                            'logs.info.account_protected',
                            ['{{ user }}' => $twitterUser->screen_name],
                            'logs'
                        );
                        throw new ProtectedAccountException($protectedAccount);
                    }

                    if (isset($twitterUser->screen_name)) {
                        $user = new User();
                        $user->setTwitterUsername($twitterUser->screen_name);
                        $user->setTwitterID($friend);
                        $user->setEnabled(false);
                        $user->setLocked(false);
                        $user->setEmail('@' . $twitterUser->screen_name);
                        $user->setStatus(0);

                        $entityManager->persist($user);
                        $entityManager->flush();
                        $publishedMessage = $translator->trans(
                            'amqp.info.user_persisted',
                            ['{{ user }}' => $twitterUser->screen_name],
                            'messages'
                        );
                        $this->logger->info($publishedMessage);
                    } else {
                        $this->logger->info(serialize($twitterUser));
                    }
                } catch (SuspendedAccountException $exception) {
                    $suspendedAccount = $translator->trans(
                        'amqp.output.suspended_account',
                        ['{{ user }}' => $friend],
                        'messages'
                    );
                    $this->sendMessage($suspendedAccount, 'info', $output, $exception);
                    $invalidUsers++;

                    // TODO Flag suspended accounts
                    // Skip suspended accounts
                    continue;
                } catch (ProtectedAccountException $exception) {
                    $protectedAccount = $translator->trans(
                        'amqp.output.protected_account',
                        ['{{ user }}' => $friend],
                        'messages'
                    );
                    $this->sendMessage($protectedAccount, 'info', $output, $exception);
                    $invalidUsers++;

                    // TODO Flag protected accounts
                    // Skip protected accounts
                    continue;
                } catch (UnavailableResourceException $exception) {
                    $unavailableResource = $translator->trans(
                        'amqp.output.unavailable_resource',
                        ['{{ user }}' => $friend],
                        'messages'
                    );
                    $this->sendMessage($unavailableResource, 'error', $output, $exception);

                    return $exception->getCode();
                }
            }

            if (isset($user)) {
                $messageBody['screen_name'] = $user->getTwitterUsername();
                $producer->setContentType('application/json');
                $producer->publish(serialize(json_encode($messageBody)), $routingKey);
                $publishedMessage = $translator->trans(
                    'amqp.info.message_published',
                    ['{{ user }}' => $messageBody['screen_name']],
                    'messages'
                );
                $this->logger->info($publishedMessage);
            }
        }

        $output->writeln(
            $translator->trans(
                'amqp.production.friendlist.success',
                ['{{ count }}' => count($friends->ids) - $invalidUsers],
                'messages'
            )
        );
    }

    /**
     * @param $message
     * @param $level
     * @param OutputInterface $output
     * @param \Exception $exception
     */
    protected function sendMessage($message, $level, OutputInterface $output, \Exception $exception)
    {
        $output->writeln($message);
        $this->logger->$level($exception->getMessage());
    }
}
