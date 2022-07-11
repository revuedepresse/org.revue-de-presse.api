<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Formatter;

use App\Twitter\Infrastructure\Publication\Formatter\PublicationFormatterInterface;

trait TweetFormatterTrait
{
    private PublicationFormatterInterface $tweetFormatter;

    public function setTweetFormatter(PublicationFormatterInterface $tweetFormatter): self
    {
        $this->tweetFormatter = $tweetFormatter;

        return $this;
    }
}
