<?php

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective;

return function (Perspective $instance) {
    if (!function_exists('openssl_random_pseudo_bytes')) {
        throw new RuntimeException('openssl extension is required to use this closure');
    }

    $data = openssl_random_pseudo_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    $perspectiveUuid  = $instance->getUuid();
    if (is_null($perspectiveUuid)) {
        $instance->setUuid($uuid);
    }

    return $instance;
};