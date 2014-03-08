<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Parser;

use WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingMessage;

/**
 * @package WeavingTheWeb\Bundle\MappingBundle\Tests\Parser
 */
class EmailParser implements ParserInterface
{
    protected $contentType = 'text/html';

    protected $charset = 'charset="UTF-8"';

    public function parseHeader($emailHeaders)
    {
        if (!is_string($emailHeaders)) {
            throw new \InvalidArgumentException('Email headers should be passed as a string');
        }

        if (preg_match('/\n\s/m', $emailHeaders)) {
            $unfoldedEmailHeaders = preg_replace('/\n\s/m', "", $emailHeaders);
        } else {
            $unfoldedEmailHeaders = $emailHeaders;
        }

        if (false !== strpos($unfoldedEmailHeaders, "\n")) {
            $lines = explode("\n", $unfoldedEmailHeaders);
        } else {
            $lines = [$unfoldedEmailHeaders];
        }

        $properties = [];
        foreach ($lines as $line) {
            if (strlen(trim($line)) > 0) {
                list($name, $value) = explode(':', $line);
                $properties[trim(strtolower($name))] = trim($value);
            }
        }

        return $properties;
    }

    /**
     * @param WeavingMessage $message
     * @return mixed
     */
    public function parseBody(WeavingMessage $message)
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
        $messageLastPart = $this->getMessageLastPart($message);
        $messageWithoutLastHeader = $this->removeLastHeader($messageLastPart);
        $messageWithoutImages = $this->removeImages($messageWithoutLastHeader);

        if (function_exists($decoder)) {
            return $decoder($messageWithoutImages);
        } else {
            return $messageWithoutImages;
        }
    }

    /**
     * @param WeavingMessage $message
     * @return mixed
     */
    protected function getMessageLastPart(WeavingMessage $message)
    {
        $body = $message->getMsgBodyHtml();
        $messageBoundary = $this->getMessageBoundary($message);
        $bodyLastPart = $this->splitOnBoundary($body, $messageBoundary);
        if ($this->containsBoundary($bodyLastPart)) {
            $bodyLastPart = $this->getMessageLastPartRecursively($bodyLastPart);
        }

        return $bodyLastPart;
    }

    /**
     * @param WeavingMessage $message
     * @return array
     */
    protected function getMessageBoundary(WeavingMessage $message)
    {
        list(, $boundaryAndNextHeaders) = explode('boundary=', $this->getMessageContentType($message));
        list($boundary) = explode("\r\n", $boundaryAndNextHeaders);

        return trim($boundary, '"');
    }

    /**
     * @param $lastMessagePart
     * @return mixed
     */
    protected function getMessageLastPartRecursively($lastMessagePart)
    {
        while ($match = preg_match('#boundary="(?<boundary>[^"]+)"#', $lastMessagePart, $matches)) {
            $boundary = $matches['boundary'];
            $messageAfterSplit = $this->splitOnBoundary($lastMessagePart, $boundary);
            if ($this->contains($messageAfterSplit, $boundary)) {
                $lastMessagePart = str_replace('boundary="' . $boundary . '"', '', $messageAfterSplit);
            } else {
                $lastMessagePart = $messageAfterSplit;
            }
        }

        return $lastMessagePart;
    }

    /**
     * @param $message
     * @param $boundary
     * @return mixed
     */
    protected function splitOnBoundary($message, $boundary)
    {
        $parts = explode('--' . $boundary, trim($message));
        // Removes double hyphens
        array_pop($parts);
        $contentTypesIndex = null;

        foreach ($parts as $index => $part) {
            $contentTypeHeader = $this->parseContentTypeHeader($part);
            if ($this->containsContentTransferEncoding($contentTypeHeader)) {
                if ($this->containsTextHtmlContentType($contentTypeHeader)) {
                    $contentTypesIndex['html'] = $index;
                    list($contentTypeValue) = explode("\r\n", $contentTypeHeader);
                    list(, $charset) = explode(';', $contentTypeValue);
                    $this->contentType = 'text/html';
                    $this->charset = trim($charset);
                }
                if ($this->containsTextPlainContentType($contentTypeHeader)) {
                    $contentTypesIndex['text'] = $index;
                }
                $contentTransferEncoding = $this->parseContentTransferEncoding($part);
                $decoder = $contentTransferEncoding . '_decode';
                $partWithoutHeader = $this->removeLastHeader($part);

                if (function_exists($decoder)) {
                    if ($this->containsImagePngContentType($contentTypeHeader)) {
                        $parts[$index] = $this->getImageAsDataURI($partWithoutHeader, 'image/png', $contentTransferEncoding);
                    } elseif ($this->containsImageJpgContentType($contentTypeHeader)) {
                        $parts[$index] = $this->getImageAsDataURI($partWithoutHeader, 'image/jpeg', $contentTransferEncoding);
                    } else {
                        $parts[$index] = $decoder($partWithoutHeader);
                    }
                } else {
                    $parts[$index] = $partWithoutHeader;
                }
            }
        }

        if (
            count($parts) > 2 &&
            array_key_exists('html', $contentTypesIndex)
        ) {
            if (array_key_exists('text', $contentTypesIndex)) {
                unset($parts[$contentTypesIndex['text']]);
            } else {
                $parts = array_slice($parts, $contentTypesIndex['html']);
            }

            return implode($parts);
        } elseif (count($parts) > 1) {
            return $parts[count($parts) - 1];
        } else {
            return $message;
        }
    }

    /**
     * @param $partWithoutHeader
     * @param $contentType
     * @param $encoding
     * @return string
     */
    protected function getImageAsDataURI($partWithoutHeader, $contentType, $encoding)
    {
        return '<img src="data:' . $contentType . ';' . $encoding . ',' . trim($partWithoutHeader) . '" />';
    }

    /**
     * @param $subject
     * @return mixed
     */
    protected function removeLastHeader($subject)
    {
        $parts = $this->getLastHeaderParts($subject);
        if (count($parts) > 1 && $this->containsContentTransferEncoding($parts[0])) {
            unset($parts[0]);
        }
        $separator = "\r\n\r\n";

        return implode($separator, $parts);
    }

    /**
     * @param $htmlBodyWithoutImages
     * @return array
     */
    protected function parseContentTypeHeader($htmlBodyWithoutImages)
    {
        $parts = $this->getLastHeaderParts($htmlBodyWithoutImages);

        return strtolower($parts[0]);
    }

    /**
     * @param WeavingMessage $message
     * @return mixed
     */
    protected function getContentTransferEncoding(WeavingMessage $message)
    {
        $lastMessagePart = $this->getMessageLastPart($message);

        return $this->parseContentTransferEncoding($lastMessagePart);
    }

    /**
     * @param $subject
     * @return mixed
     */
    protected function parseContentTransferEncoding($subject)
    {
        $keyValuePairs = explode("\r\n", $subject);
        $contentTransferEncoding = null;
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
     * @param $subject
     * @return bool
     */
    protected function containsContentTransferEncoding($subject)
    {
        return $this->contains(strtolower($subject), 'content-transfer-encoding');
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
     * @param $htmlBodyWithoutImages
     * @return array
     */
    protected function getLastHeaderParts($htmlBodyWithoutImages)
    {
        $separator = "\r\n\r\n";

        return explode($separator, trim($htmlBodyWithoutImages));
    }

    /**
     * @param WeavingMessage $message
     * @return bool
     */
    protected function hasBoundary(WeavingMessage $message)
    {
        $contentType = $this->getMessageContentType($message);

        return $this->containsBoundary($contentType);
    }

    /**
     * @param WeavingMessage $message
     * @return mixed
     */
    public function guessMessageContentType(WeavingMessage $message)
    {
        if ($this->isPlainTextMessage($message)) {
            return $this->getMessageContentType($message);
        } else {
            return $this->contentType . '; ' . $this->charset;
        }
    }

    /**
     * @param WeavingMessage $message
     * @return mixed
     */
    public function getMessageContentType(WeavingMessage $message)
    {
        $properties = $this->parseHeader($message->getHeader()->getHdrValue());

        return $properties['content-type'];
    }

    /**
     * @param $haystack
     * @return bool
     */
    protected function containsBoundary($haystack)
    {
        return $this->contains($haystack, 'boundary=');
    }

    /**
     * @param $haystack
     * @return bool
     */
    protected function containsTextHtmlContentType($haystack)
    {
        return $this->contains($haystack, 'text/html');
    }

    /**
     * @param $haystack
     * @return bool
     */
    protected function containsImagePngContentType($haystack)
    {
        return $this->contains($haystack, 'image/png');
    }

    /**
     * @param $haystack
     * @return bool
     */
    protected function containsImageJpgContentType($haystack)
    {
        return $this->contains($haystack, 'image/jpg') || $this->contains($haystack, 'image/jpeg') ;
    }

    /**
     * @param $haystack
     * @return bool
     */
    protected function containsTextPlainContentType($haystack)
    {
        return $this->contains($haystack, 'text/plain');
    }

    /**
     * @param WeavingMessage $message
     * @return bool
     */
    public function isPlainTextMessage(WeavingMessage $message)
    {
        $properties = $this->parseHeader($message->getHeader()->getHdrValue());

        return false !== strpos($properties['content-type'], 'text/plain');
    }

    /**
     * @param WeavingMessage $message
     * @return mixed|string
     */
    protected function removeMessageImages(WeavingMessage $message)
    {
        $body = $this->removeMessageHeaders($message);

        if (!$this->isPlainTextMessage($message) && false !== strpos($body, '<img')) {
            return $this->removeImages($body);
        } else {
            return $body;
        }
    }

    /**
     * @param $subject
     * @return mixed
     */
    protected function removeImages($subject)
    {
        $imagePatterns = $this->getImageHTMLNodesPatterns();
        $bodyWithoutImages = $subject;
        foreach ($imagePatterns as $imagePattern) {
            $bodyWithoutImages = preg_replace($imagePattern, '', $subject);
        }

        return trim($bodyWithoutImages);
    }

    /**
     * @return array
     */
    protected function getImageHTMLNodesPatterns()
    {
        return $imagePatterns = [
            '/<img[^>]+>/m',
        ];
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
}