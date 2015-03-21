<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Loader;

use Doctrine\Common\Util\Inflector;

/**
 * Class ClosureLoader
 * @package WeavingTheWeb\Bundle\MappingBundle\Loader
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ClosureLoader
{
    protected $resourcesDir;

    protected $mapperName;

    public function setResourcesDir($dir)
    {
        $this->resourcesDir = $dir;
    }

    public function setMapperName($name)
    {
        $this->mapperName = $name;
    }

    public function load()
    {
        $closurePath = $this->resourcesDir . '/mappers/closures/' . Inflector::camelize($this->mapperName) . '.php';

        if (!file_exists($closurePath)) {
            throw new \InvalidArgumentException;
        } else {
            $closure = require $closurePath;

            return $closure;
        }
    }
} 