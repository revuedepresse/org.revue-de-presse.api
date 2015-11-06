<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Loader;

use Doctrine\Common\Util\Inflector;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ClosureLoader
{
    protected $resourcesDir;

    protected $mapperName;

    protected $callback;

    public function setResourcesDir($dir)
    {
        $this->resourcesDir = $dir;
    }

    public function setMapperName($name)
    {
        $this->mapperName = $name;
    }

    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    public function load()
    {
        if ($this->callback) {
            $scriptName = 'runCallback.php';
        } else {
            $scriptName = Inflector::camelize($this->mapperName) . '.php';
        }

        $closurePath = $this->resourcesDir . '/mappers/closures/' . $scriptName ;

        if (!file_exists($closurePath)) {
            throw new \InvalidArgumentException(sprintf('Invalid path to closure ("%s")', $closurePath));
        } else {
            if ($this->callback) {
                // Required by closure below
                $callback = $this->callback;
            }
            $closure = require $closurePath;

            return $closure;
        }
    }
} 
