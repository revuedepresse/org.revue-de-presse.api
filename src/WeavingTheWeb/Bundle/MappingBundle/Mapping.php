<?php

namespace WeavingTheWeb\Bundle\MappingBundle;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Mapping implements \ArrayAccess, \Countable, \Iterator
{
    protected $mappers = [];

    protected $position = 0;

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->mappers);
    }

    public function offsetGet($offset)
    {
        return $this->mappers[$offset];
    }


    public function offsetSet($offset, $value)
    {
        $this->mappers[$offset] = $value;

        return $this->mappers;
    }

    public function offsetUnset($offset)
    {
        unset($this->mappers[$offset]);
    }

    public function getMappers()
    {
        return $this->mappers;
    }

    /**
     * @param array $collection
     */
    public function walk(array $collection)
    {
        foreach ($this->mappers as $mapper) {
            array_walk($collection, $mapper);
        }
    }

    public function count()
    {
        return count($this->mappers);
    }

    public function current()
    {
        return current($this->mappers);
    }

    public function next()
    {
        $this->position++;

        return next($this->mappers);
    }

    public function key()
    {
        return key($this->mappers);
    }

    public function valid()
    {
        return $this->count($this->mappers) > $this->position;
    }

    public function rewind()
    {
        $this->position = 0;

        return reset($this->mappers);
    }
}
