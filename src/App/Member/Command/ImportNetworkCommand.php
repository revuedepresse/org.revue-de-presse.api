<?php

namespace App\Member\Command;

use App\Console\CommandReturnCodeAwareInterface;
use App\Member\Entity\NotFoundMember;
use App\Member\MemberInterface;
use App\Member\Repository\MemberSubscribeeRepository;
use App\Member\Repository\MemberSubscriptionRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException;

class ImportNetworkCommand extends Command implements CommandReturnCodeAwareInterface
{
    const OPTION_MEMBER_LIST = 'member-list';

    const OPTION_MEMBER_NAME = 'member-name';

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Accessor
     */
    public $accessor;

    /**
     * @var MemberSubscribeeRepository
     */
    public $memberSubscribeeRepository;

    /**
     * @var MemberSubscriptionRepository
     */
    public $memberSubscriptionRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    public function configure()
    {
        $this->setName('import-network')
            ->setDescription('Import subscriptions and subscribees of each member in a member list.')
            ->addOption(
                self::OPTION_MEMBER_LIST,
                null,
                InputOption::VALUE_OPTIONAL,
                'A comma-separated list of member screen names'
            )
            ->addOption(
                self::OPTION_MEMBER_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'The name of a member, which network should be imported'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $memberList = $this->input->getOption(self::OPTION_MEMBER_LIST);
        $memberName = $this->input->getOption(self::OPTION_MEMBER_NAME);

        $validMemberList = strlen(trim($memberList)) > 0;

        if (!$validMemberList && strlen(trim($memberName)) === 0) {
            throw new \LogicException(
                'There should be at least a non-empty member list or a member name passed as argument'
            );
        }

        if ($validMemberList) {
            $members = explode(',', $memberList);

            return $this->saveNetwork($members);
        }

        return $this->saveNetwork([$memberName]);
    }

    /**
     * @param MemberInterface $member
     * @param array           $subscriptions
     * @return array
     */
    function saveMemberSubscriptions(MemberInterface $member, array $subscriptions)
    {
        return array_map(
            function (string $subscription) use ($member) {
                try {
                    $subscriptionMember = $this->accessor->ensureMemberHavingIdExists(intval($subscription));
                } catch (NotFoundMemberException $exception) {
                    return new NotFoundMember();
                } catch (ProtectedAccountException $exception) {
                    return new ProtectedAccountException();
                } catch (\Exception $exception) {
                    $this->logger->critical($exception->getMessage());

                    return;
                }

                $this->logger->info(sprintf(
                    'About to save subscription of member "%s" for member "%s"',
                    $member->getTwitterUsername(),
                    $subscriptionMember->getTwitterUsername()
                ));

                return $this->memberSubscriptionRepository->saveMemberSubscription($member, $subscriptionMember);
            },
            $subscriptions
        );
    }

    /**
     * @param MemberInterface $member
     * @param array           $subscribees
     * @return array
     */
    function saveMemberSubscribees(MemberInterface $member, array $subscribees)
    {
        return array_map(
            function (string $subscribee) use ($member) {
                try {
                    $subscribeeMember = $this->accessor->ensureMemberHavingIdExists(intval($subscribee));
                } catch (NotFoundMemberException $exception) {
                    return new NotFoundMember();
                } catch (ProtectedAccountException $exception) {
                    return new ProtectedAccountException();
                } catch (\Exception $exception) {
                    $this->logger->critical($exception->getMessage());

                    return;
                }

                $this->logger->info(sprintf(
                    'About to save subscribees of member "%s" for member "%s"',
                    $member->getTwitterUsername(),
                    $subscribeeMember->getTwitterUsername()
                ));

                return $this->memberSubscribeeRepository->saveMemberSubscribee($member, $subscribeeMember);
            },
            $subscribees
        );
    }

    /**
     * @param $members
     * @return int
     */
    private function saveNetwork($members): int
    {
        array_walk(
            $members,
            function (string $member) {
                $member = $this->accessor->ensureMemberHavingNameExists($member);

                $friends = $this->accessor->showUserFriends($member->getTwitterUsername());
                if ($member instanceof MemberInterface) {
                    $this->saveMemberSubscriptions($member, $friends->ids);
                }

                $subscribees = $this->accessor->showMemberSubscribees($member->getTwitterUsername());
                if ($member instanceof MemberInterface) {
                    $this->saveMemberSubscribees($member, $subscribees->ids);
                }
            }
        );

        return self::RETURN_STATUS_SUCCESS;
    }
}
