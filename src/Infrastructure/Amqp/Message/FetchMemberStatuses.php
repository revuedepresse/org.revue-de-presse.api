<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\Message;

class FetchMemberStatuses implements FetchPublicationInterface
{
    use FetchPublicationTrait;
}

