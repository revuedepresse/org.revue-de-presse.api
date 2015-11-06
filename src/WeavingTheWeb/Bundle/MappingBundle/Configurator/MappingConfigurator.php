<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Configurator;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective,
    WeavingTheWeb\Bundle\DashboardBundle\Export\ExporterInterface,
    WeavingTheWeb\Bundle\MappingBundle\Factory\MapperAwareInterface;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class MappingConfigurator
{
    /**
     * @var array $loaders
     */
    protected $loaders = [];

    /**
     * @var array $mappersSettings
     */
    protected $mappersSettings = [];

    public function addLoader($name, $service)
    {
        $this->loaders[$name] = $service;
    }

    /**
     * @param $settings
     * @return mixed
     */
    public function setMappersSettings($settings)
    {
        return $this->mappersSettings = $settings;
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
                    'name' => $settings['name'],
                    'callback' => $this->extractCallback($settings)
                ];
            }
        }

        $mappingFactory->setMappers($mappers);
    }

    /**
     * @param $settings
     * @return array
     */
    protected function extractCallback($settings)
    {
        if (array_key_exists('callback', $settings)) {
            $requirements = $settings['callback'];
        } else {
            $requirements = [];
        }

        return $requirements;
    }

    /**
     * @param ExporterInterface $exporter
     * @return string
     */
    public function configurePerspectiveExporter(ExporterInterface $exporter)
    {
        $mapperName = 'export_perspective_as_json';
        $this->mappersSettings[] = [
            'name' => $mapperName,
            'loader' => 'closure',
            'callback' => function (Perspective $perspective) use ($exporter) {
                $exporter->addExportable($perspective);
                $exporter->export();

                return $perspective;
            }
        ];

        return $mapperName;
    }
}
