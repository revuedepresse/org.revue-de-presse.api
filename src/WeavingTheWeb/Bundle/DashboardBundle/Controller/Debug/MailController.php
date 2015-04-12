<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller\Debug;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request;

use WeavingTheWeb\Bundle\MailBundle\Entity\Message;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @Extra\Route("/debug")
 */
class MailController extends Controller
{
    /**
     * @Extra\Route("/serialize/mail/{id}", name="weaving_the_web_dashboard_debug_serialize_mail")
     * @Extra\Template("WeavingTheWebDashboardBundle:Mail:Debug/serialize_mail.html.twig")
     */
    public function serializeAction(Message $message)
    {
        $header = $message->getHeader();

        return [
            'message_id' => $message->getId(),
            'message' => $message::toJson($message),
            'header' => $header::toJson($message->getHeader()),
        ];
    }

    /**
     * @Extra\Route("/show/body/{id}", name="weaving_the_web_dashboard_debug_show_body")
     * @Extra\Template("WeavingTheWebDashboardBundle:Mail:Debug/show_body.html.twig")
     */
    public function showBodyAction(Message $message)
    {
        /** @var \WeavingTheWeb\Bundle\MailBundle\Storage\GmailAwareImap $storage */
        $storage = $this->get('weaving_the_web_mail.storage.imap');
        $body = $storage->fetchBodyByUid($message->getHeader()->getImapUid());

        return [
            'message_id' => $message->getId(),
            'message' => json_encode([
                'body' => base64_encode($body),
            ], JSON_PRETTY_PRINT)
        ];
    }

    /**
     * @Extra\Route("/update/body/{id}", name="weaving_the_web_dashboard_debug_update_body")
     * @Extra\Template("WeavingTheWebDashboardBundle:Mail:Debug/update_body.html.twig")
     */
    public function updateBodyAction(Message $message, Request $request)
    {
        /** @var \WeavingTheWeb\Bundle\MailBundle\Storage\GmailAwareImap $storage */
        $storage = $this->get('weaving_the_web_mail.storage.imap');
        $body = $storage->fetchBodyByUid($message->getHeader()->getImapUid());

        $message->setHtmlBody($body);

        /** @var \WeavingTheWeb\Bundle\MailBundle\Parser\EmailParser $emailParser */
        $emailParser = $this->get('weaving_the_web_mail.parser.email');
        $emailParser->encodeMessageHTMLBody($message);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($message);
        $entityManager->flush();

        if ($request->query->get('referrer')) {
            $requestUri = urldecode($request->query->get('referrer'));

            return new RedirectResponse($request->getSchemeAndHttpHost() . $requestUri);
        }

        return [
            'message_id' => $message->getId()
        ];
    }
}