<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Factory;

/**
 * @package WeavingTheWeb\Bundle\MappingBundle\Factory
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class MappingFactory implements MapperAwareInterface
{
    /**
     * @var array $mappers
     */
    protected $mappers;

    /**
     * @var string $mappingClass
     */
    protected $mappingClass;

    /**
     * @param array $mappers
     */
    public function setMappers(array $mappers = [])
    {
        $this->mappers = $mappers;
    }

    /**
     * @param $mappingClass
     */
    public function setMappingClass($mappingClass)
    {
        $this->mappingClass = $mappingClass;
    }

    public function get()
    {
        $mappingClass = $this->mappingClass;
        $mapping = new $mappingClass;

        foreach ($this->mappers as $mapper) {

            /**
             * @var $loader \WeavingTheWeb\Bundle\MappingBundle\Loader\ClosureLoader
             */
            $loader = $mapper['loader'];

            $loader->setResourcesDir(__DIR__ . '/../Resources');
            $loader->setMapperName($mapper['name']);

            $mapping[$mapper['name']] = $loader->load();
        }

        return $mapping;
    }
}