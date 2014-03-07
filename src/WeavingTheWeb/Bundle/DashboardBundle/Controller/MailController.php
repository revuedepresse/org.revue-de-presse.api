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
        } else {
            $response->headers->set('Content-Type', 'text/html; charset="UTF-8"');
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

        if ($this->hasBoundary($message)) {
            return $this->decodeMessage($message);
        } else {
            return $this->removeMessageImages($message);
        }
    }

    /**
     * @param WeavingMessage $message
     * @return mixed
     */
    protected function decodeMessage(WeavingMessage $message)
    {
        $contentTransferEncoding = $this->getContentTransferEncoding($message);
        $decoder = $contentTransferEncoding . '_decode';
        $lastMessagePart = $this->getLastMessagePart($message);
        $messageWithoutLastHeader = $this->removeLastHeader($lastMessagePart);

        if (function_exists($decoder)) {
            return $decoder($messageWithoutLastHeader);
        } else {
            return $messageWithoutLastHeader;
        }
    }

    /**
     * @param $htmlBodyWithoutImages
     * @return mixed
     */
    protected function removeLastHeader($htmlBodyWithoutImages)
    {
        $separator = "\r\n\r\n";
        $parts = $this->getLastHeaderParts($htmlBodyWithoutImages);
        if (count($parts) > 1) {
            unset($parts[0]);
        }

        return implode($separator, $parts);
    }

    /**
     * @param WeavingMessage $message
     * @return mixed
     */
    protected function getContentTransferEncoding(WeavingMessage $message)
    {
        $lastMessagePart = $this->getLastMessagePart($message);
        $keyValuePairs = explode("\r\n", $lastMessagePart);
        foreach ($keyValuePairs as $keyValuePair) {
            $loweredKeyValuePair = strtolower($keyValuePair);
            if ($this->containsContentTransferEncoding($loweredKeyValuePair)) {
                list(, $contentTransferEncoding) = explode(':', $loweredKeyValuePair);

                break;
            }
        }

        if (!isset($contentTransferEncoding)) {
            return 'quoted_printable';
        } else {
            return str_replace('-', '_', trim($contentTransferEncoding));
        }
    }

    /**
     * @param $loweredKeyValuePair
     * @return bool
     */
    protected function containsContentTransferEncoding($loweredKeyValuePair)
    {
        return $this->contains($loweredKeyValuePair, 'content-transfer-encoding');
    }

    /**
     * @param $haystack
     * @param $needle
     * @return bool
     */
    protected function contains($haystack, $needle)
    {
        return false !== strpos($haystack, $needle);
    }

    /**
     * @param WeavingMessage $message
     * @return mixed
     */
    protected function getLastMessagePart(WeavingMessage $message)
    {
        $messageParts = $this->getMessageParts($message);
        /** Removes the double hyphen preceding the last boundary */
        array_pop($messageParts);

        return array_pop($messageParts);
    }

    /**
     * @param WeavingMessage $message
     * @return array
     */
    protected function getMessageParts(WeavingMessage $message)
    {
        $bodyWithoutImages = $this->removeMessageImages($message);
        list(, $boundaryAndNextHeaders) = explode('boundary=', $this->getMessageContentType($message));
        list($boundary) = explode("\r\n", $boundaryAndNextHeaders);

        return explode('--' . trim($boundary, '"'), $bodyWithoutImages);
    }

    /**
     * @param $htmlBodyWithoutImages
     * @return array
     */
    protected function getLastHeaderParts($htmlBodyWithoutImages)
    {
        $separator = "\r\n\r\n";

        return explode($separator, $htmlBodyWithoutImages);
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

        return $properties['content-type'];
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

        return false !== strpos($properties['content-type'], 'text/plain');
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
     * @param WeavingMessage $message
     * @return mixed|string
     */
    protected function removeMessageImages(WeavingMessage $message)
    {
        $body = $this->removeMessageHeaders($message);

        if (!$this->isPlainTextMessage($message) && false !== strpos($body, '<img')) {
            $imagePatterns = $this->getImageHTMLNodesPatterns();
            $bodyWithoutImages = $body;
            foreach ($imagePatterns as $imagePattern) {
                $bodyWithoutImages = preg_replace($imagePattern, '', $body);
            }

            return $bodyWithoutImages;
        } else {
            return $body;
        }
    }

    /**
     * @return array
     */
    protected function getImageHTMLNodesPatterns()
    {
        return $imagePatterns = [
            '/<img(?!src=)(?:[^>]*)src=(?:\'|")([^"\']+)(?:\'|")[^>]*>/',
            '/<img(?!SRC=)(?:[^>]*)SRC=(?:\'|")([^"\']+)(?:\'|")[^>]*>/'
        ];
    }
}