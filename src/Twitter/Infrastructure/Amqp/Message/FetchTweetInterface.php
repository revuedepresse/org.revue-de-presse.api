<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

use App\Twitter\Domain\Http\Model\TokenInterface;

interface FetchTweetInterface
{
    public const BEFORE = 'before';

    public function dateBeforeWhichStatusAreCollected(): ?string;

    public function token(): TokenInterface;
}
