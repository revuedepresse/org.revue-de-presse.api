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
        $response = new Response();
        if ($this->isPlainTextMessage($message)) {
            $response->headers->set('Content-Type', $this->getMessageContentType($message));
        }
        $response->setContent(
            $this->renderView(
                'WeavingTheWebDashboardBundle:Mail:show.html.twig',
                [
                    'message' => $this->parseMessage($message)
                ]
            )
        );
        $response->send();
    }


    /**
     * @param WeavingMessage $message
     * @return mixed
     */
    protected function parseMessage(WeavingMessage $message)
    {
        $bodyWithoutImages = $this->removeMessageImages($message);

        if ($this->hasBoundary($message)) {
            list(, $boundaryAndNextHeaders) = explode('boundary=', $this->getMessageContentType($message));
            list($boundary) = explode("\r\n", $boundaryAndNextHeaders);
            $messageParts = explode('--' . trim($boundary, '"'), $bodyWithoutImages);
            /** Removes the double hyphen preceding the last boundary */
            array_pop($messageParts);
            $htmlBodyWithoutImages = array_pop($messageParts);

            return $this->removeLastHeader($htmlBodyWithoutImages);
        } else {
            return $bodyWithoutImages;
        }
    }

    /**
     * @param $htmlBodyWithoutImages
     * @return mixed
     */
    protected function removeLastHeader($htmlBodyWithoutImages)
    {
        $separator = "\r\n\r\n";
        $parts = explode($separator, $htmlBodyWithoutImages);
        unset($parts[0]);

        return implode($separator, $parts);
    }

    /**
     * @param WeavingMessage $message
     * @return bool
     */
    protected function hasBoundary(WeavingMessage $message)
    {
        $contentType = $this->getMessageContentType($message);

        return false !== strpos($contentType, 'boundary=');
    }

    /**
     * @param WeavingMessage $message
     * @return mixed
     */
    protected function getMessageContentType(WeavingMessage $message)
    {
        /** @var \WeavingTheWeb\Bundle\MappingBundle\Parser\EmailHeadersParser $emailHeadersParser */
        $emailHeadersParser = $this->get('weaving_the_web_mapping.parser.email_headers');
        $properties = $emailHeadersParser->parse($message->getHeader()->getHdrValue());

        return $properties['Content-Type'];
    }

    /**
     * @param WeavingMessage $message
     * @return bool
     */
    protected function isPlainTextMessage(WeavingMessage $message)
    {
        /** @var \WeavingTheWeb\Bundle\MappingBundle\Parser\EmailHeadersParser $emailHeadersParser */
        $emailHeadersParser = $this->get('weaving_the_web_mapping.parser.email_headers');
        $properties = $emailHeadersParser->parse($message->getHeader()->getHdrValue());

        return false !== strpos($properties['Content-Type'], 'text/plain');
    }

    /**
     * @param WeavingMessage $message
     * @return string
     */
    protected function removeMessageHeaders(WeavingMessage $message)
    {
        $htmlBody = $message->getMsgBodyHtml();
        $bodyProposal = '';
        while ($bodyProposal !== $htmlBody) {
            $bodyProposal = str_replace($message->getHeader()->getHdrValue(), '', $htmlBody);
            $htmlBody = $bodyProposal;
        }

        return $htmlBody;
    }

    /**
     * @param $mail
     * @return array
     */
    protected function hasContentTransferEncodingHeader($mail)
    {
        return false !== strpos($mail, 'Content-Transfer-Encoding:');
    }

    /**
     * @param WeavingMessage $message
     * @return mixed|string
     */
    protected function removeMessageImages(WeavingMessage $message)
    {
        $body = $this->removeMessageHeaders($message);
        $decodedBody = quoted_printable_decode($body);

        if (!$this->isPlainTextMessage($message) && false !== strpos($decodedBody, '<img')) {
            $imagePatterns = [
                '/<img(?!src=)(?:[^>]*)src=(?:\'|")([^"\']+)(?:\'|")[^>]*>/',
                '/<img(?!SRC=)(?:[^>]*)SRC=(?:\'|")([^"\']+)(?:\'|")[^>]*>/'
            ];

            foreach ($imagePatterns as $imagePattern) {
                $decodedBody = preg_replace($imagePattern, '', $decodedBody);
            }

            return $decodedBody;
        } else {
            return $decodedBody;
        }
    }
}