<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;
use WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingHeader,
    WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingMessage;

/**
 * Class SaveEmailsCommand
 * @package WeavingTheWeb\Bundle\ApiBundle\Command
 */
class SaveEmailsCommand extends ContainerAwareCommand
{
    const IMAP_HOST = 'imap.gmail.com';

    const IMAP_PORT = 993;

    const IMAP_FLAGS = '/ssl/novalidate-cert';

    const SEPARATOR_HASH_WORDS = '_h_h_h_';

    const SEPARATOR_LABEL_SUBJECT = '_$_$_$_';

    const SEPARATOR_LEVEL = '/';

    /**
     * @see Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('weaving_the_web:email:save')
            ->setDescription('Save emails')
            ->setAliases(['wtw:mail:x']);
    }

    /**
     * @see Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $allMailsMailboxes = $this->getMailboxes('/^\[Gmail\]\/All\sMail/');
            $this->import('*', $allMailsMailboxes);
        } catch (\Exception $exception) {
            $output->writeln($exception->getMessage());
        }
    }

    /**
     * Get mailboxes
     *
     * @param    string $pattern pattern
     * @param    string $stream IMAP stream
     * @param    resource $resource IMAP resource
     * @return    integer        message sequence
     */
    public function getMailboxes(
        $pattern,
        $stream = null,
        $resource = null
    ) {
        $mailboxes = array();

        if (is_null($stream)) {
            $stream = $this->getImapStream();
        }

        if (is_null($resource)) {
            $resource = $this->openImapStream($stream);
        }

        $mailBoxes = imap_getmailboxes($resource, $stream, '*');

        foreach ($mailBoxes as $mailbox) {
            $mailboxName =
                str_replace(
                    $stream,
                    '',
                    $mailbox->name
                );

            if ($match = preg_match($pattern, $mailboxName, $matches)) {
                $mailboxes[] = $mailboxName;
            }
        }

        return $mailboxes;
    }

    /**
     *
     * Open an IMAP stream
     *
     * @param   string $stream IMAP stream
     * @return  resource IMAP stream
     */
    public function openImapStream($stream = null)
    {
        if (is_null($stream)) {
            $stream = $this->getImapStream();
        }
        $username = $this->getContainer()->getParameter('imap.username');
        $password = $this->getContainer()->getParameter('imap.password');

        if ($imapStream = imap_open($stream, $username, $password)) {
            $stream = $imapStream;
        }

        return $stream;
    }

    /**
     * Import messages into the database
     *
     * @param null $subject
     * @param null $labels
     * @param null $mailbox
     * @param null $resource
     * @throws \Exception
     */
    public function import(
        $subject = null,
        $labels = null,
        $mailbox = null,
        $resource = null
    ) {
        if (is_null($subject)) {
            $subject = 'slashdot';
        }

        $maxUIDs =
        $search_results = array();

        if (is_null($mailbox) && is_null($resource)) {
            $mailbox = $this->getImapStream();
        }
        if (is_null($resource)) {
            $resource = $this->openImapStream($mailbox);
        }

        /**
         *
         * Initialize the search label to be used
         * if no specific parameter is passed as argument
         *
         */

        if (
            is_null($labels) ||
            !is_array($labels) ||
            !count($labels)
        ) {
            $label = '[Gmail]/All Mail';
            $keywords = $label . ' ' . self::SEPARATOR_LABEL_SUBJECT . ' ' . $subject;

            $labels =
            $_labels = array($keywords => $label);
        }

        foreach ($labels as $index => $label) {
            $keywords = $label . ' ' . self::SEPARATOR_LABEL_SUBJECT . ' ' . $subject;

            if ((!$index && !isset($_labels[0])) || $index) {
                $_labels[$keywords] = $label;
            }
            $maxUIDs[$keywords] = $this->getMaxUID($keywords);
            imap_reopen($resource, $mailbox . $label);

            $criteria = 'SUBJECT "' . $subject . '"';
            $search_results[$label] = imap_search($resource, $criteria, SE_UID);
        }

        reset($_labels);
        reset($search_results);
        list(, $uids) = each($search_results);
        reset($search_results);

        // Get the last uid
        end($uids);
        list(, $last_uid) = each($uids);
        reset($uids);

        foreach ($search_results as $label => $uids) {
            $uids = $this->refineUIDs($subject, $mailbox, $resource, $uids, $label, $maxUIDs, $last_uid);
            $this->importLabelMessages($subject, $resource, $uids, $label);
        }
    }

    /**
     * Get an IMAP stream
     *
     * @return  string    IMAP mailbox settings
     */
    public function getImapStream()
    {
        return '{' . self::IMAP_HOST . ':' . self::IMAP_PORT . self::IMAP_FLAGS . '}';
    }

    /**
     * Get the maximum uid for provided search criteria
     *
     * @param    string $criteria criteria
     * @return    mixed    uid
     */
    public function getMaxUID($criteria)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var \Doctrine\ORM\EntityRepository $repository */
        $headerRepository = $entityManager->getRepository('WeavingTheWebLegacyProviderBundle:WeavingHeader');

        return $headerRepository->getMaxUID($criteria);
    }

    /**
     * @param $subject
     * @param $resource
     * @param $uids
     * @param $label
     * @throws \Exception
     */
    protected function importLabelMessages($subject, $resource, $uids, $label)
    {
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->getContainer()->get('logger');

        foreach ($uids as $uid) {
            $logger->info(sprintf('Accessing message with uid #%s', $uid));

            $header = $this->importHeader(
                $resource,
                $uid,
                $subject,
                $label
            );
            $this->importMessage($resource, $uid, $header);
        }
    }

    /**
     * @param $resource
     * @param $uid
     * @param $subject
     * @param $label
     * @return WeavingHeader
     * @throws \Exception
     */
    protected function importHeader($resource, $uid, $subject, $label)
    {
        $headerText = imap_fetchheader($resource, $uid, FT_UID);
        if (!strlen(trim($headerText))) {
            throw new \Exception('Invalid header');
        } else {
            $keywords =
                $label . ' ' .
                self::SEPARATOR_LABEL_SUBJECT . ' ' .
                $subject;

            /** Save headers and their corresponding messages */
            $header = new WeavingHeader();
            $header->setHdrValue($headerText)
                ->setHdrImapUid($uid)
                ->setHdrKeywords($keywords)
                ->setHdrHash($this->makeHeaderHash($uid, $headerText, $keywords))
                ->setCntId(0)
                ->setRclId(0)
                ->setHdrDateCreation(new \DateTime())
                ->setHdrDateUpdate(new \DateTime());

            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
            $entityManager->persist($header);
            $entityManager->flush();

            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->getContainer()->get('logger');
            $logger->info(sprintf('Persisted header of uid #%s', $uid));

            return $header;
        }
    }

    /**
     * @param $uid
     * @param $headerText
     * @param $keywords
     * @return string
     */
    protected function makeHeaderHash($uid, $headerText, $keywords)
    {
        return md5(
            $uid . self::SEPARATOR_HASH_WORDS .
            $headerText . self::SEPARATOR_HASH_WORDS .
            (is_null($keywords) ? '' : $keywords)
        );
    }

    /**
     * @param $resource
     * @param $uid
     * @param WeavingHeader $header
     * @throws \Exception
     */
    protected function importMessage($resource, $uid, WeavingHeader $header)
    {
        $body = imap_body($resource, $uid, FT_UID);
        if (!strlen(trim($body))) {
            throw new \Exception('Invalid body');
        } else {
            $message = new WeavingMessage();
            $hash = md5(
                $uid . self::SEPARATOR_HASH_WORDS . $header->getHdrKeywords() .
                str_repeat(self::SEPARATOR_HASH_WORDS, 2) . $body
            );
            $message->setMsgHash($hash)
                ->setHdrId($header->getHdrId())
                ->setMsgBodyHtml($body)
                ->setMsgType(0);

            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
            $entityManager->persist($message);
            $entityManager->flush();

            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->getContainer()->get('logger');
            $logger->info(sprintf('Persisted message with header of uid #%s', $uid));
        }
    }

    /**
     * @param $subject
     * @param $mailbox
     * @param $resource
     * @param $uids
     * @param $label
     * @param $maxUIDs
     * @param $last_uid
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function refineUIDs($subject, $mailbox, $resource, $uids, $label, $maxUIDs, $last_uid)
    {
        if (is_array($uids) && count($uids)) {
            $_keywords = $label . ' ' . self::SEPARATOR_LABEL_SUBJECT . ' ' . $subject;
            $max_uid = intval($maxUIDs[$_keywords]);

            /**
             * Look up the index of the last recorded UID
             * for provided search criteria
             */

            while (!($maxUidIndex = array_search($max_uid, $uids, true)) && ($max_uid < $last_uid)) {
                $max_uid++;
            }
            if ($maxUidIndex === false) {
                $maxUidIndex = -1;
            }

            imap_reopen($resource, $mailbox . $label);

            $offset = $maxUidIndex;
            while (!array_key_exists($offset, $uids)) {
                $offset--;
            }
            $uids = array_slice($uids, $offset + 1);

            return $uids;

        } else {
            throw new \InvalidArgumentException('Provided UIDs are invalid');
        }
    }
}
