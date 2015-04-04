<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
                'subject' => $parser->parseSubject($message['subject'])
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
                $messages[$index] = [
                    'sender' => $parser->decodeSender($message['sender']),
                    'subject' => $parser->decodeSubject($message['subject']),
                    'id' => $message['mailBodyId']
                ];
            }
        } else {
            $finder = $this->container->get('fos_elastica.finder.mail.message');
            $messages = $finder->find($keywords, 100);

            /**
             * @var \WeavingTheWeb\Bundle\MailBundle\Entity\Message $message
             */
            foreach ($messages as $index => $message) {
                $messages[$index] = [
                    'sender' => $parser->decodeSender($message->getHeader()->getFrom()),
                    'subject' => $parser->decodeSubject($message->getHeader()->getSubject()),
                    'id' => $message->getId()
                ];
            }
        }

        $collapsedMailTitle = $this->get('translator')->trans('title.collapsed_mails', [], 'mail');

        return [
            'emails' => $messages,
            'title' => $collapsedMailTitle
        ];
    }

    /**
     * @param integer $id
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @Extra\Route("/{id}", name="weaving_the_web_dashboard_mail_show")
     * @Extra\Template("WeavingTheWebDashboardBundle:Mail:show.html.twig")
     */
    public function showAction($id)
    {
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
}