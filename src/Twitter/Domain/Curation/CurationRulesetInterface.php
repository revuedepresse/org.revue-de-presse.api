<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation;

interface CurationRulesetInterface
{
    public const RULE_BEFORE                            = 'before';
    public const RULE_CURSOR                            = 'cursor';
    public const RULE_FILTER_BY_TWEET_OWNER_USERNAME    = 'filter_by_tweet_owner_username';
    public const RULE_IGNORE_WHISPERS                   = 'ignore_whispers';
    public const RULE_INCLUDE_OWNER                     = 'include_owner';
    public const RULE_LIST                              = 'list';
    public const RULE_LISTS                             = 'lists';
    public const RULE_SCREEN_NAME                       = 'screen_name';

    public function tweetCreationDateFilter(): ?string;

    public function isCurationCursorActive(): ?int;
}