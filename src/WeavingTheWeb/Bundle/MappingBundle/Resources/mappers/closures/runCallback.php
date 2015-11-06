<?php

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective;

if (!isset($callback) || !is_callable($callback)) {
    throw new \Exception('Invalid callback');
}

return function (Perspective $perspective) use ($callback) {
    return $callback($perspective);
};
