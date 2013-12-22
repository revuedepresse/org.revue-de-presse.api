<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
use WTW\UserBundle\Entity\User;

/**
 * Class ProduceUserListsMembersCommand
 * @package WeavingTheWeb\Bundle\AmqpBundle\Command
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProduceListsMembersCommand extends AccessorAwareCommand
{
    public function configure()
    {
        $this->setName('weaving_the_web:amqp:produce:lists_members')
            ->setDescription('Produce a message to get lists members status')
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
            'list',
            null,
            InputOption::VALUE_OPTIONAL,
            'A list to which production is restricted to'
        )->setAliases(array('wtw:amqp:tw:prd:lm'));
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
        $lists = $this->accessor->getUserLists($input->getOption('screen_name'));

        $messageBody = $tokens;

        /**
         * @var \WTW\UserBundle\Repository\UserRepository $userRepository
         */
        $userRepository = $this->getContainer()->get('wtw_user.repository.user');
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        if ($input->hasOption('list') && !is_null($input->getOption('list'))) {
            $listRestriction = $input->getOption('list');
        } else {
            $listRestriction = null;
        }

        foreach ($lists as $list) {
            if (is_null($listRestriction) || $list->name === $listRestriction) {
                $members = $this->accessor->getListMembers($list->id);

                foreach ($members->users as $friend) {

                    $result = $userRepository->findOneBy(['twitterID' => $friend->id]);
                    if (count($result) === 1) {
                        $user = $result;
                    } else {
                        $twitterUser = $this->accessor->showUser($friend->screen_name);
                        if (isset($twitterUser->screen_name)) {
                            $message = '[publishing new message produced for "' . ( $twitterUser->screen_name ) . '"]';
                            $this->logger->info($message);

                            $user = new User();
                            $user->setTwitterUsername($twitterUser->screen_name);
                            $user->setTwitterID($friend->id);
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
                $output->writeln($translator->trans('amqp.list_members.production.success', [
                    '{{ count }}' => count($members->users),
                    '{{ list }}' => $list->name,
                ]));
            }
        }
    }
}