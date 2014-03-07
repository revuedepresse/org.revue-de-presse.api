<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Response;

use WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingHeader,
    WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingMessage;

/**
 * @package WeavingTheWeb\Bundle\DashboardBundle\Controller
 * @Extra\Route("/mail")
 */
class MailController extends Controller
{
    /**
     * @Extra\Route("/all", name="weaving_the_web_dashboard_mail_all")
     * @Extra\Template("WeavingTheWebDashboardBundle:Mail:all.html.twig")
     */
    public function allAction()
    {
        /**
         * @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Repository\WeavingMessageRepository $messageRepository
         */
        $messageRepository = $this->getDoctrine()->getRepository('WeavingTheWebLegacyProviderBundle:WeavingMessage');

        return [
            'emails' => $messageRepository->findLast(10),
            'title' => 'All mail'
        ];
    }

    /**
     * @Extra\Route("/{id}", name="weaving_the_web_dashboard_mail_show")
     */
    public function showAction($id)
    {
        /** @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Repository\WeavingMessageRepository $messageRepository */
        $messageRepository = $this->getDoctrine()->getRepository('WeavingTheWebLegacyProviderBundle:WeavingMessage');
        /** @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingMessage $message */
        $message = $messageRepository->findOneBy(['msgId' => $id]);

        /** @var \WeavingTheWeb\Bundle\MappingBundle\Parser\EmailParser $parser */
        $parser = $this->get('weaving_the_web_mapping.parser.email');

        $response = new Response();
        if ($parser->isPlainTextMessage($message)) {
            $response->headers->set('Content-Type', $parser->getMessageContentType($message));
        } else {
            $response->headers->set('Content-Type', 'text/html; charset="UTF-8"');
        }
        $response->setContent(
            $this->renderView(
                'WeavingTheWebDashboardBundle:Mail:show.html.twig',
                [
                    'message' => $parser->parseBody($message)
                ]
            )
        );
        $response->send();
    }
}