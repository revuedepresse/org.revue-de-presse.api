<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
}
