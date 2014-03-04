<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

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

        $encoding = 'Content-Transfer-Encoding:';
        $messageBody = $message->getMsgBodyHtml();
        $messageBodyProposal = $this->refineMessageBody($messageBody, $encoding);
        while ($messageBodyProposal !== $messageBody) {
            $messageBody = $messageBodyProposal;
            $messageBodyProposal = $this->refineMessageBody($messageBody, $encoding);
        }

        $header = $message->getHeader();
        /** @var \WeavingTheWeb\Bundle\MappingBundle\Parser\EmailHeadersParser $emailHeadersParser */
        $emailHeadersParser = $this->get('weaving_the_web_mapping.parser.email_headers');
        $properties = $emailHeadersParser->parse($header->getHdrValue());

        $response = new Response();
        if (false !== strpos($properties['Content-Type'], 'text/plain')) {
            $plainText = true;
            $response->headers->set('Content-Type', $properties['Content-Type']);
        } else {
            $plainText = false;
        }
        $response->setContent(
            $this->renderView(
                'WeavingTheWebDashboardBundle:Mail:show.html.twig',
                [
                    'emailBody' => $messageBody,
                    'plainText' => $plainText
                ]
            )
        );
        $response->send();
    }

    /**
     * @param $messageBody
     * @param $encoding
     * @return string
     * @throws \Exception
     */
    protected function refineMessageBody($messageBody, $encoding)
    {
        $positionProposal = strpos($messageBody, $encoding);
        if ($positionProposal !== false) {
            $headerEndsAt = $positionProposal;
            $positionProposal = strpos(substr($messageBody, $headerEndsAt + 1), $encoding);
            if ($positionProposal !== false) {
                $headerEndsAt = $positionProposal;
            }

            if (($bodyStartsAtProposal = strpos($messageBody, "\n\r\n", $headerEndsAt)) !== false) {
                $bodyStartsAt = $bodyStartsAtProposal;
            } elseif (($bodyStartsAtProposal = strpos($messageBody, "\n", $headerEndsAt)) !== false) {
                $bodyStartsAt = $bodyStartsAtProposal;
            } else {
                throw new \Exception('No obvious separation between body and header');
            }
            $messageBody = substr($messageBody, $bodyStartsAt + 1);
        }

        return $messageBody;
    }
}
