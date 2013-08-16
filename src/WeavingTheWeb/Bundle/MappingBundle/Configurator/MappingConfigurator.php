<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Configurator;

use WeavingTheWeb\Bundle\MappingBundle\Factory\MapperAwareInterface;

/**
 * Class MappingConfigurator
 * @package WeavingTheWeb\Bundle\MappingBundle\Configurator
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class MappingConfigurator
{
    /**
     * @var array $loaders
     */
    protected $loaders = [];

    /**
     * @var array $mapperSettings
     */
    protected $mapperSettings = [];

    public function addLoader($name, $service)
    {
        $this->loaders[$name] = $service;
    }

    /**
     * @param $settings
     * @return mixed
     */
    public function setMapperSettings($settings)
    {
        return $this->mapperSettings = $settings;
    }

    public function configure(MapperAwareInterface $mappingFactory)
    {
        if (empty($this->mappersSettings)) {
            $this->mappersSettings = [
                [ 'name' => 'update_perspective_uuid', 'loader' => 'closure' ],
                [ 'name' => 'update_perspective_hash', 'loader' => 'closure' ],
            ];
        }

        $mappers = [];

        foreach ($this->mappersSettings as $settings) {
            $loader = $settings['loader'];

            if (!isset($this->loaders[$loader])) {
                throw new \RuntimeException(sprintf('Invalid loader %s', $loader));
            } else {
                $mappers[] = [
                    'loader' => $this->loaders[$loader],
                    'name' => $settings['name']
                ];
            }
        }

        $mappingFactory->setMappers($mappers);
    }
} 