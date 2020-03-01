<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\Message;

class FetchMemberStatus implements FetchPublicationInterface
{
    use FetchPublicationTrait;
}

