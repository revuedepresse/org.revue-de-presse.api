<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Parser;

/**
 * @package WeavingTheWeb\Bundle\MappingBundle\Tests\Parser
 */
class EmailHeadersParser implements ParserInterface
{
    public function parse($emailHeaders)
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
                $properties[trim($name)] = trim($value);
            }
        }

        return $properties;
    }
}