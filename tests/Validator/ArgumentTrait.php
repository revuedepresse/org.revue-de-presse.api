<?php

namespace App\Test;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
trait ArgumentTrait
{
    /**
     * Checks if a value is traversable
     *
     * @param $value
     *
     * @return bool
     */
    public function isTraversable($value)
    {
        return is_object($value) || is_array($value);
    }

    /**
     * Throws an invalid argument exception
     *
     * @param      $name
     * @param      $value
     * @param null $expected
     *
     * @throws \InvalidArgumentException
     */
    public function throwInvalidArgumentException($name, $value, $expected = null)
    {
        $expectation = is_null($expected) ? '' : sprintf(' (%s expected)', $expected);

        throw new \InvalidArgumentException(sprintf(
            'Invalid "%s" of type "%s"' . $expectation,
            $name,
            gettype($value)
        ));
    }
}
