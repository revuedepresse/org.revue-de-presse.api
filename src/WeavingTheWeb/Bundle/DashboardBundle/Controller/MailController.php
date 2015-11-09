<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use WeavingTheWeb\Bundle\MailBundle\Entity\Header,
    WeavingTheWeb\Bundle\MailBundle\Entity\Message;

use WeavingTheWeb\Bundle\MailBundle\Exception\InvalidSequenceNumber;

/**
 * Handles all mail related actions
 *
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @Extra\Route("/mail")
 */
class MailController extends Controller
{
    /**
     * Show all paginated mail
     *
     * @Extra\Route("/all", name="weaving_the_web_dashboard_mail_all")
     * @Extra\Template("WeavingTheWebDashboardBundle:Mail:all.html.twig")
     *
     * @return array
     */
    public function allAction()
    {
        /** @var \WeavingTheWeb\Bundle\MailBundle\Repository\MessageRepository $messageRepository */
        $messageRepository = $this->get('weaving_the_web_mail.repository.message');
        $messages = $messageRepository->findLast(10, 0);

        /** @var \WeavingTheWeb\Bundle\MailBundle\Parser\EmailParser $parser */
        $parser = $this->get('weaving_the_web_mail.parser.email');

        foreach ($messages as $index => $message) {
            $messages[$index] = [
                'mailBodyId' => $message['mailBodyId'],
                'subject' => $parser->decodeSubject($message['subject'])
            ];
        }

        return [
            'active_menu_item' => 'emails',
            'emails' => $messages,
            'title' => 'All mail'
        ];
    }

    /**
     * @Extra\Route(
     *      "/collapsed/{keywords}",
     *      name="weaving_the_web_dashboard_mail_show_collapsed_mails",
     *      requirements={"keywords" = "[^/]+"},
     *      defaults={"keywords" = null}
     * )
     * @Extra\Template("WeavingTheWebDashboardBundle:Mail:collapsed_mails.html.twig")
     *
     * @param null $keywords
     * @return array
     */
    public function showCollapsedMailsAction($keywords = null)
    {
        /** @var \WeavingTheWeb\Bundle\MailBundle\Parser\EmailParser $parser */
        $parser = $this->get('weaving_the_web_mail.parser.email');

        if (is_null($keywords)) {
            /** @var \WeavingTheWeb\Bundle\MailBundle\Repository\MessageRepository $messageRepository */
            $messageRepository = $this->get('weaving_the_web_mail.repository.message');
            $messages = $messageRepository->findLast(100, 0);

            foreach ($messages as $index => $message) {
                $subject = $this->ensureNonEmptySubject($parser->decodeSubject($message['subject']));

                $messages[$index] = [
                    'sender'    => $parser->decodeSender($message['sender']),
                    'subject'   => $subject,
                    'id'        => $message['mailBodyId'],
                    'date'      => $message['date'],
                ];
            }
        } else {
            $finder = $this->container->get('fos_elastica.finder.mail.message');
            $messages = $finder->find($keywords, 100);

            /**
             * @var \WeavingTheWeb\Bundle\MailBundle\Entity\Message $message
             */
            foreach ($messages as $index => $message) {
                $subject = $this->ensureNonEmptySubject($parser->decodeSubject($message->getHeader()->getSubject()));

                $messages[$index] = [
                    'sender'    => $parser->decodeSender($message->getHeader()->getFrom()),
                    'subject'   => $subject,
                    'id'        => $message->getId(),
                    'date'      => $message->getHeader()->getDate()
                ];
            }

            $messages = $this->orderMessagesByDescendingDate($messages);
        }

        $collapsedMailTitle = $this->get('translator')->trans('title.collapsed_mails', [], 'mail');

        return [
            'active_menu_item' => 'emails',
            'emails' => $messages,
            'title' => $collapsedMailTitle
        ];
    }

    /**
     * @param $candidate
     * @return string
     */
    protected function ensureNonEmptySubject($candidate)
    {
        if (empty($candidate)) {
            $subject = '<no subject>';
        } else {
            $subject = $candidate;
        }

        return $subject;
    }

    /**
     * @return mixed
     */
    protected function orderMessagesByDescendingDate($messages)
    {
        usort($messages, [$this, 'compareHeaders']);

        return $messages;
    }

    /**
     * @param array $leftMember
     * @param array $rightMember
     * @return int
     */
    public function compareHeaders(array $leftMember, array $rightMember)
    {
        if ($leftMember['date'] === $rightMember['date']) {
            return 0;
        }

        if ($leftMember['date'] > $rightMember['date']) {
            return -1;
        } else {
            return 1;
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return RedirectResponse
     *
     * @Extra\Route("/{id}", name="weaving_the_web_dashboard_mail_show")
     * @Extra\Template("WeavingTheWebDashboardBundle:Mail:show.html.twig")
     */
    public function showAction($id, Request $request)
    {
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            if ($request->query->get('update') === 'body') {
                $updateBodyUrl = $this->generateUrl('weaving_the_web_dashboard_debug_update_body', ['id' => $id]);
                $referrer = str_replace('?update=body', '', $request->getRequestUri());

                return new RedirectResponse($updateBodyUrl . '?referrer=' . urlencode($referrer));
            }
        }

        /** @var \WeavingTheWeb\Bundle\MailBundle\Repository\MessageRepository $messageRepository */
        $messageRepository = $this->get('weaving_the_web_mail.repository.message');

        /** @var \WeavingTheWeb\Bundle\MailBundle\Entity\Message $message */
        $message = $messageRepository->findOneBy(['id' => $id]);

        if (is_null($message)) {
            throw new NotFoundHttpException('This message can not be found');
        }

        /** @var \WeavingTheWeb\Bundle\MailBundle\Parser\EmailParser $parser */
        $parser = $this->get('weaving_the_web_mail.parser.email');
        $parsedBody = $parser->parseBody($message);

        $response = new Response();
        $response->setContent(
            $this->renderView(
                'WeavingTheWebDashboardBundle:Mail:show.html.twig',
                ['message' => $parsedBody]
            )
        );
        $response->headers->set('Content-Type', $parser->guessMessageContentType($message));
        $response->send();
    }

    /**
     * @Extra\Route(
     *      "/move-to-spam/{id}",
     *      name="weaving_the_web_dashboard_mail_move_to_spam",
     *      requirements={"id" = "\d+"},
     * )
     * @Extra\Method({"POST"})
     *
     * @param Message $message
     * @return Response
     */
    public function moveToSpam(Message $message)
    {
        $message->reportAsSpam();

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManager();

        /** @var \WeavingTheWeb\Bundle\MailBundle\Storage\GmailAwareImap $storage */
        $storage = $this->get('weaving_the_web_mail.storage.imap');

        try {
            $storage->moveToSpam($message->getHeader()->getImapUid());
        } catch (InvalidSequenceNumber $exception) {
            $message->setSequenceNumberUnavailable(true);
            $message->reportAsSpam(true);

            $this->get('logger')->error($exception->getMessage());
        } catch (\Exception $exception) {
            return new Response(
                sprintf('Could not move message with id #%d to spam', $message->getId()),
                400
            );
        }

        $entityManager->persist($message);
        $entityManager->flush();

        $collapsedMailsUrl = $this->generateUrl('weaving_the_web_dashboard_mail_show_collapsed_mails');

        return new RedirectResponse($collapsedMailsUrl);
    }
}
