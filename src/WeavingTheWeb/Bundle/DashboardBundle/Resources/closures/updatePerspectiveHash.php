<?php

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective;

return function (Perspective $perspective) {
    $perspective->setHash(sha1($perspective->getUuid()));

    return $perspective;
};