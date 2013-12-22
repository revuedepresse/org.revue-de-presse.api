<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;
use WTW\UserBundle\Entity\User;

/**
 * Class ProduceUserFriendListCommand
 * @package WeavingTheWeb\Bundle\AmqpBundle\Command\Twitter
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProduceUserFriendListCommand extends AccessorAwareCommand
{
    public function configure()
    {
        $this->setName('weaving_the_web:amqp:produce:user_timeline')
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
        )->setAliases(array('wtw:amqp:tw:prd:utl'));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var \OldSound\RabbitMqBundle\RabbitMq\Producer $producer
         */
        $producer = $this->getContainer()->get('old_sound_rabbit_mq.weaving_the_web_amqp.twitter.user_status_producer');
        $tokens = $this->getTokens($input);

        $this->setUpLogger();
        $this->setupAccessor($tokens);
        $friends = $this->accessor->showUserFriends($input->getOption('screen_name'));

        $messageBody = $tokens;


        /**
         * @var \WTW\UserBundle\Repository\UserRepository $userRepository
         */
        $userRepository = $this->getContainer()->get('wtw_user.repository.user');
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        foreach ($friends->ids as $friend) {

            $result = $userRepository->findOneBy(['twitterID' => $friend]);
            if (count($result) === 1) {
                $user = $result;
            } else {
                $twitterUser = $this->accessor->showUser($friend);
                if (isset($twitterUser->screen_name)) {
                    $message = '[publishing new message produced for "' . ( $twitterUser->screen_name ) . '"]';
                    $this->logger->info($message);

                    $user = new User();
                    $user->setTwitterUsername($twitterUser->screen_name);
                    $user->setTwitterID($friend);
                    $user->setEnabled(false);
                    $user->setLocked(false);
                    $user->setEmail('@' . $twitterUser->screen_name);
                    $user->setStatus(0);

                    $entityManager->persist($user);
                    $entityManager->flush();
                } elseif (isset($twitterUser->errors) && is_array($twitterUser->errors) && isset($twitterUser->errors[0])) {
                    $message = print_r($twitterUser->errors[0]->message, true);
                    $this->logger->error($message);

                    break;
                }
            }

            $messageBody['screen_name'] = $user->getTwitterUsername();
            $producer->setContentType('application/json');
            $producer->publish(serialize(json_encode($messageBody)));
        }

        /**
         * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator
         */
        $translator = $this->getContainer()->get('translator');
        $output->writeln($translator->trans('amqp.friendlist.production.success', ['{{ count }}' => count($friends->ids)]));
    }
}