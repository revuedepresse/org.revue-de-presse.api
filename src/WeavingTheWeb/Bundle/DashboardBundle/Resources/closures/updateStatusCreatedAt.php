<?php

use WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream;
use Psr\Log\LoggerInterface;

return function (UserStream $userStream, $status, LoggerInterface $logger = null) {
    if (is_null($userStream->getUpdatedAt())) {
        if (isset($status->created_at)) {
            $userStream->setCreatedAt(new \Datetime($status->created_at));
            $userStream->setUpdatedAt(new \Datetime());
            $userStream->setApiDocument(json_encode($status));
        } else {
            if (isset($status->errors) && $status->errors[0] !== 34) {
                $logger->info($status->errors[0]->message);
            } else {
                $logger->error($status->errors[0]->message);
            }
        }
    }

    return $userStream;
};