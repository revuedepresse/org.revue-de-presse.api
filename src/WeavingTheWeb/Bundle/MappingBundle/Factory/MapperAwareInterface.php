<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Factory;

/**
 * Interface MapperAwareInterface
 * @package WeavingTheWeb\Bundle\MappingBundle\Factory
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface MapperAwareInterface
{
    public function setMappers(array $mappers = []);
} 