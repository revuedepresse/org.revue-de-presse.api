<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation;

interface CurationStrategyInterface
{
    public const RULE_BEFORE                 = 'before';
    public const RULE_SCREEN_NAME            = 'screen_name';
    public const RULE_MEMBER_RESTRICTION     = 'member_restriction';
    public const RULE_INCLUDE_OWNER          = 'include_owner';
    public const RULE_IGNORE_WHISPERS        = 'ignore_whispers';
    public const RULE_LIST                   = 'list';
    public const RULE_LISTS                  = 'lists';
    public const RULE_CURSOR                 = 'cursor';

    public function dateBeforeWhichPublicationsAreCollected(): ?string;

    public function shouldFetchPublicationsFromCursor(): ?int;
}