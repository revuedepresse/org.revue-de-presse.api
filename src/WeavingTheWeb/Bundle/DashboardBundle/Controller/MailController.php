<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles all mail related actions
 *
 * @package WeavingTheWeb\Bundle\DashboardBundle\Controller
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
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
        $message = $messageRepository->findOneBy(['msgId' => $id]);

        if (is_null($message)) {
            throw new NotFoundHttpException('This message can not be found');
        }

        /** @var \WeavingTheWeb\Bundle\MailBundle\Parser\EmailParser $parser */
        $parser = $this->get('weaving_the_web_mail.parser.email');

        $response = new Response();
        $response->setContent(
            $this->renderView(
                'WeavingTheWebDashboardBundle:Mail:show.html.twig',
                [
                    'message' => $parser->parseBody($message)
                ]
            )
        );
        $response->headers->set('Content-Type', $parser->guessMessageContentType($message));
        $response->send();
    }
}