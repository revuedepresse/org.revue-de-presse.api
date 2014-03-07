<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Parser;

use WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingMessage;

/**
 * @package WeavingTheWeb\Bundle\MappingBundle\Tests\Parser
 */
interface ParserInterface
{
    public function parseHeader($subject);

    public function parseBody(WeavingMessage $subject);
}